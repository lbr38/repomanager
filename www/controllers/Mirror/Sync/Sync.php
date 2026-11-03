<?php

namespace Controllers\Mirror\Sync;

use Controllers\Download\Parallel;
use Controllers\Filesystem\Directory;
use Controllers\Process;
use Controllers\Utils\Convert;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;
use Exception;

/**
 *  Mirroring: upstream metadata and packages are copied as is, nothing is rebuilt or re-signed
 *
 *  Each package type (Deb, Rpm...) parses its metadata to build the list of files to retrieve, with
 *  their expected size and checksum, and relies on this class to:
 *   - download files in parallel and verify them (see Controllers\Download\Parallel)
 *   - hard link unchanged files from the previous snapshot
 *   - delete files that are no longer referenced upstream
 */
abstract class Sync
{
    // Number of full sync attempts if upstream repository changes during the sync
    protected const MAX_ATTEMPTS = 3;

    protected int $taskId;
    protected string $workingDir;
    protected string $tempDir;
    protected string $url;
    protected bool $nonCompliant = false;
    protected bool $checkSignature = true;
    protected array $params = [];
    protected string|null $previousSnapshotDir = null;
    protected array $expectedFiles = [];
    protected int $totalSize = 0;
    protected array $stats = [];
    protected $taskLogSubStepController;
    protected $downloader;

    public function __construct(int $taskId, string $workingDir)
    {
        $this->taskId = $taskId;
        $this->workingDir = $workingDir;
        $this->tempDir = TEMP_DIR . '/mirror-task-' . $taskId;
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
        $this->downloader = new \Controllers\Download\Parallel($taskId);
    }

    /**
     *  Retrieve upstream metadata and packages into the working directory
     *  Every retrieved file must be declared with addExpected() (fetch() does it)
     */
    abstract protected function syncFiles(): void;

    /**
     *  Return true if the upstream repository has changed since syncFiles() started
     */
    abstract protected function upstreamChanged(): bool;

    /**
     *  Set source repository definition (URL, SSL and basic authentication...)
     */
    public function setSource(array $definition): void
    {
        if (empty($definition['url'])) {
            throw new Exception('Source repository URL is empty');
        }

        $this->url = rtrim($definition['url'], '/');
        $this->nonCompliant = ($definition['non-compliant'] ?? 'false') == 'true';

        // SSL authentication material is written to temporary files, as expected by curl
        $sslFiles = [];

        foreach (['certificate', 'private-key', 'ca-certificate'] as $key) {
            if (empty($definition['ssl-authentication'][$key])) {
                continue;
            }

            $this->createTempDir();

            if (!file_put_contents($this->tempDir . '/ssl-' . $key, $definition['ssl-authentication'][$key])) {
                throw new Exception('Could not write SSL ' . $key . ' to temporary file');
            }

            $sslFiles[$key] = $this->tempDir . '/ssl-' . $key;
        }

        $this->downloader->setSslAuthentication($sslFiles['certificate'] ?? null, $sslFiles['private-key'] ?? null, $sslFiles['ca-certificate'] ?? null);

        // Basic authentication credentials
        if (!empty($definition['basic-authentication']['username'])) {
            $this->downloader->setBasicAuthentication($definition['basic-authentication']['username'], $definition['basic-authentication']['password'] ?? '');
        }
    }

    /**
     *  Set mirror parameters (dist, components, releasever, arch...)
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    /**
     *  Enable or disable the upstream metadata GPG signature check
     */
    public function setCheckSignature(bool $checkSignature): void
    {
        $this->checkSignature = $checkSignature;
    }

    /**
     *  Set previous snapshot directory, unchanged files will be hard linked from it
     */
    public function setPreviousSnapshotDir(string $path): void
    {
        $this->previousSnapshotDir = $path;
    }

    /**
     *  Return total size (in bytes) of the mirrored files
     */
    public function getTotalSize(): int
    {
        return $this->totalSize;
    }

    /**
     *  Start mirroring
     */
    public function mirror(): void
    {
        if (!is_dir($this->workingDir)) {
            if (!mkdir($this->workingDir, 0770, true)) {
                throw new Exception('Could not create working directory ' . $this->workingDir);
            }
        }

        $this->createTempDir();

        try {
            for ($attempt = 1; $attempt <= self::MAX_ATTEMPTS; $attempt++) {
                $this->expectedFiles = [];

                $this->syncFiles();

                // Upstream repository may have been updated while syncing, in this case metadata and packages
                // might not match anymore: start again (already retrieved files are kept and not downloaded again)
                $this->taskLogSubStepController->new('check-consistency', 'CHECKING CONSISTENCY', 'Make sure the source repository has not been updated during the sync');

                if (!$this->upstreamChanged()) {
                    $this->taskLogSubStepController->completed('Source repository has not changed, the mirror is consistent');
                    break;
                }

                if ($attempt == self::MAX_ATTEMPTS) {
                    throw new Exception('The source repository has been updated during each of the ' . self::MAX_ATTEMPTS . ' sync attempts, please retry later');
                }

                $this->taskLogSubStepController->warning('The source repository has been updated during the sync, syncing again to get a consistent mirror (attempt ' . ($attempt + 1) . '/' . self::MAX_ATTEMPTS . ')');
            }

            $this->taskLogSubStepController->new('clean', 'CLEANING', 'Delete files that are no longer referenced by the source repository');
            $deleted = $this->cleanUnexpectedFiles();
            $this->taskLogSubStepController->completed($deleted > 0 ? $deleted . ' file(s) deleted' : 'Nothing to delete');
        } finally {
            Directory::deleteRecursive($this->tempDir);
        }
    }

    /**
     *  Retrieve a list of files into the working directory
     *  Each file is an array: ['path' => relative path, 'url' => URL, 'size' => int|null, 'hash' => string|null, 'algo' => string|null]
     *
     *  $optional: a missing file (404) is not an error, it is just not retrieved
     *  $force: always download the file, even if it already exists (used for files without known checksum)
     *  $verifyExisting: verify the checksum of an existing file before keeping it, otherwise its size is enough
     *  (files are only renamed to their final name once verified, so an existing file has already been verified)
     *  $substepTitle: display each download as a task sub-step with this title, otherwise the progress is displayed in the current sub-step
     *
     *  Return the relative paths of the files that are present after retrieval
     */
    protected function fetch(array $files, bool $optional = false, bool $force = false, bool $verifyExisting = false, string|null $substepTitle = null): array
    {
        $queue = [];
        $present = [];
        $this->stats = ['total' => 0, 'downloaded' => 0, 'downloadedBytes' => 0, 'linked' => 0, 'existing' => 0];

        // Remove duplicates (e.g. 'Architecture: all' packages are listed in each arch index)
        $files = array_column($files, null, 'path');
        $this->stats['total'] = count($files);

        foreach ($files as $file) {
            $this->checkPath($file['path']);

            $target = $this->workingDir . '/' . $file['path'];

            if (!$force) {
                // File already exists in the working directory (resumed task or previous sync attempt)
                if (is_file($target) and $this->matches($target, $file, $verifyExisting)) {
                    $present[] = $file['path'];
                    $this->addExpected($file['path']);
                    $this->stats['existing']++;
                    continue;
                }

                // File exists in the previous snapshot with the same size (and checksum if required): hard link it
                if (!empty($this->previousSnapshotDir)) {
                    $previous = $this->previousSnapshotDir . '/' . $file['path'];

                    if (is_file($previous) and $this->matches($previous, $file, $verifyExisting)) {
                        $this->link($previous, $target);
                        $present[] = $file['path'];
                        $this->addExpected($file['path']);
                        $this->stats['linked']++;
                        continue;
                    }
                }
            }

            $file['target'] = $target;
            $queue[$file['path']] = $file;
        }

        // Files are queued by path, the downloader returns the paths of the optional files not found
        $missing = $this->downloader->download($queue, $optional, $substepTitle);
        $this->stats['downloaded'] = $this->downloader->getDownloadedCount();
        $this->stats['downloadedBytes'] = $this->downloader->getDownloadedBytes();

        foreach ($queue as $file) {
            if (!in_array($file['path'], $missing)) {
                $present[] = $file['path'];
                $this->addExpected($file['path']);
            }
        }

        return $present;
    }

    /**
     *  Retrieve the packages into the working directory, as repositories do: a 'DOWNLOADING PACKAGES' sub-step
     *  followed by a sub-step for each downloaded package, unchanged packages are only counted in the summary
     */
    protected function fetchPackages(array $packages, string $note = ''): void
    {
        $this->taskLogSubStepController->new('retrieve-packages', 'DOWNLOADING PACKAGES', $note);
        $this->fetch($packages, false, false, false, 'DOWNLOADING PACKAGE');
        $this->taskLogSubStepController->completed($this->stats['total'] . ' package(s): ' . $this->fetchSummary(), 'retrieve-packages');
    }

    /**
     *  Return a file entry to retrieve with fetch(), size and checksum are verified when known
     */
    protected function entry(string $path, string $url, int|null $size = null, string|null $hash = null, string|null $algo = null): array
    {
        return ['path' => $path, 'url' => $url, 'size' => $size, 'hash' => $hash, 'algo' => $algo];
    }

    /**
     *  Return a human readable summary of the latest fetch() call, e.g. '184 downloaded (818M), 12 unchanged'
     */
    protected function fetchSummary(): string
    {
        $summary = [];

        if ($this->stats['downloaded'] > 0) {
            $summary[] = $this->stats['downloaded'] . ' downloaded (' . Convert::sizeToHuman($this->stats['downloadedBytes']) . ')';
        }

        if ($this->stats['linked'] > 0) {
            $summary[] = $this->stats['linked'] . ' unchanged since previous snapshot';
        }

        if ($this->stats['existing'] > 0) {
            $summary[] = $this->stats['existing'] . ' already retrieved by a previous attempt';
        }

        if (empty($summary)) {
            return 'nothing to retrieve';
        }

        return implode(', ', $summary);
    }

    /**
     *  Return true if an existing file matches the expected size (and checksum if required)
     */
    private function matches(string $path, array $file, bool $verifyChecksum): bool
    {
        if ($verifyChecksum) {
            return Parallel::verify($path, $file['size'], $file['hash'], $file['algo']);
        }

        // Without known size, an existing file cannot be trusted
        if (is_null($file['size'])) {
            return false;
        }

        return filesize($path) == $file['size'];
    }

    /**
     *  Hard link a file, replacing the target if it exists
     */
    protected function link(string $source, string $target): void
    {
        $this->createParentDir($target);

        if (file_exists($target) or is_link($target)) {
            if (!unlink($target)) {
                throw new Exception('Could not delete ' . $target);
            }
        }

        if (!link($source, $target)) {
            throw new Exception('Could not create hard link ' . $target);
        }
    }

    /**
     *  Declare a file as part of the mirror, other files are deleted at the end of the sync
     */
    protected function addExpected(string $path): void
    {
        $this->expectedFiles[$path] = true;
    }

    /**
     *  Check that a relative path coming from upstream metadata stays inside the working directory
     */
    protected function checkPath(string $path): void
    {
        if ($path === '' or str_starts_with($path, '/') or str_contains($path, "\0") or in_array('..', explode('/', $path))) {
            throw new Exception('Invalid file path in upstream metadata: ' . htmlspecialchars($path));
        }
    }

    /**
     *  Normalize a relative path coming from upstream metadata (e.g. './pool/file.deb' => 'pool/file.deb')
     */
    protected function normalizePath(string $path): string
    {
        $segments = array_filter(explode('/', $path), function ($segment) {
            return $segment !== '' and $segment !== '.';
        });

        return implode('/', $segments);
    }

    /**
     *  Check GPG signature of a file with the keys imported in Repomanager, return the signer identity
     *  If $outputFile is specified, the signed content is written to it (clearsigned files only)
     */
    protected function checkGpgSignature(string $signatureFile, string $clearFile = '', string $outputFile = ''): string
    {
        // Force english output to parse it
        // Keys are imported in trustedkeys.gpg, it must be specified: gpgv reads trustedkeys.kbx by default when it exists
        $command = 'LC_ALL=C /usr/bin/gpgv --homedir ' . escapeshellarg(GPGHOME) . ' --keyring ' . escapeshellarg(GPGHOME . '/trustedkeys.gpg');

        if (!empty($outputFile)) {
            if (file_exists($outputFile)) {
                unlink($outputFile);
            }

            $command .= ' --output ' . escapeshellarg($outputFile);
        }

        $command .= ' ' . escapeshellarg($signatureFile);

        if (!empty($clearFile)) {
            $command .= ' ' . escapeshellarg($clearFile);
        }

        $myprocess = new Process($command);
        $myprocess->execute();
        $output = $myprocess->getOutput();
        $exitCode = $myprocess->getExitCode();
        $myprocess->close();

        if (preg_match("/Can't check signature: No public key/", $output)) {
            throw new Exception('No GPG key could verify the signature of <code>' . basename($signatureFile) . '</code>. Please check that you have imported the GPG key used to sign the repository.<br><pre class="codeblock">' . htmlspecialchars($output) . '</pre>');
        }

        if ($exitCode != 0) {
            throw new Exception('Invalid GPG signature of <code>' . basename($signatureFile) . '</code>:<br><pre class="codeblock">' . htmlspecialchars($output) . '</pre>');
        }

        if (preg_match('/Good signature from "([^"]+)"/', $output, $matches)) {
            return htmlspecialchars($matches[1]);
        }

        return '';
    }

    /**
     *  Open a (possibly compressed) metadata file for reading, return a stream to close with closeFile()
     */
    protected function openFile(string $path)
    {
        $commands = [
            'gz'  => '/usr/bin/gzip -dc',
            'xz'  => '/usr/bin/xz -dc',
            'bz2' => '/usr/bin/bzip2 -dc',
            'zst' => '/usr/bin/zstd -dc'
        ];

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        if (isset($commands[$extension])) {
            $stream = popen($commands[$extension] . ' ' . escapeshellarg($path) . ' 2>/dev/null', 'r');
        } else {
            $stream = fopen($path, 'r');
        }

        if ($stream === false) {
            throw new Exception('Could not open ' . $path);
        }

        return ['stream' => $stream, 'compressed' => isset($commands[$extension]), 'path' => $path];
    }

    /**
     *  Close a file opened with openFile()
     */
    protected function closeFile(array $file): void
    {
        if (!$file['compressed']) {
            fclose($file['stream']);
            return;
        }

        if (pclose($file['stream']) != 0) {
            throw new Exception('Could not uncompress ' . $file['path']);
        }
    }

    /**
     *  Uncompress a (possibly compressed) metadata file to the specified output file
     */
    protected function uncompressToFile(string $path, string $output): void
    {
        $file = $this->openFile($path);
        $outputStream = fopen($output, 'w');

        if ($outputStream === false) {
            throw new Exception('Could not open ' . $output . ' for writing');
        }

        stream_copy_to_stream($file['stream'], $outputStream);
        fclose($outputStream);

        $this->closeFile($file);
    }

    /**
     *  Parse a Debian control-like file (fields separated by blank lines) and call $callback for each stanza
     *  Field names are lowercased, multi-line values are joined with "\n"
     */
    protected function parseStanzas(string $path, callable $callback): void
    {
        $file = $this->openFile($path);
        $stanza = [];
        $field = null;

        while (($line = fgets($file['stream'])) !== false) {
            $line = rtrim($line, "\r\n");

            // Blank line: end of stanza
            if (trim($line) === '') {
                if (!empty($stanza)) {
                    $callback($stanza);
                }

                $stanza = [];
                $field = null;
                continue;
            }

            // Continuation line of a multi-line field
            if ($line[0] == ' ' or $line[0] == "\t") {
                if (!is_null($field)) {
                    $stanza[$field] .= "\n" . trim($line);
                }
                continue;
            }

            $position = strpos($line, ':');

            if ($position === false) {
                continue;
            }

            $field = strtolower(substr($line, 0, $position));
            $stanza[$field] = trim(substr($line, $position + 1));
        }

        if (!empty($stanza)) {
            $callback($stanza);
        }

        $this->closeFile($file);
    }

    /**
     *  Delete files that are not part of the mirror anymore (and empty directories), compute total size
     *  Return the number of deleted files
     */
    private function cleanUnexpectedFiles(): int
    {
        $deleted = 0;
        $this->totalSize = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->workingDir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            $path = $item->getPathname();
            $relativePath = substr($path, strlen($this->workingDir) + 1);

            if ($item->isDir() and !$item->isLink()) {
                // Delete directory if empty
                if (!(new FilesystemIterator($path))->valid()) {
                    rmdir($path);
                }
                continue;
            }

            if (isset($this->expectedFiles[$relativePath]) and !$item->isLink()) {
                $this->totalSize += $item->getSize();
                continue;
            }

            if (!unlink($path)) {
                throw new Exception('Could not delete ' . $path);
            }

            $deleted++;
        }

        return $deleted;
    }

    /**
     *  Create the parent directory of a file if it does not exist
     */
    private function createParentDir(string $path): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0770, true) and !is_dir($dir)) {
                throw new Exception('Could not create directory ' . $dir);
            }
        }
    }

    /**
     *  Create the task temporary directory if it does not exist
     */
    private function createTempDir(): void
    {
        if (!is_dir($this->tempDir)) {
            if (!mkdir($this->tempDir, 0770, true)) {
                throw new Exception('Could not create temporary directory ' . $this->tempDir);
            }
        }
    }
}
