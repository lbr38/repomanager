<?php

namespace Controllers\Mirror\Sync;

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
 *   - download files in parallel and verify them
 *   - hard link unchanged files from the previous snapshot
 *   - delete files that are no longer referenced upstream
 */
abstract class Sync
{
    // Number of simultaneous downloads
    protected const PARALLEL_DOWNLOADS = 6;

    // Number of retries for failed downloads
    protected const RETRIES = 3;

    // Number of full sync attempts if upstream repository changes during the sync
    protected const MAX_ATTEMPTS = 3;

    // Number of processed files between each progress output
    protected const PROGRESS_STEP = 1000;

    protected int $taskId;
    protected string $workingDir;
    protected string $tempDir;
    protected string $url;
    protected bool $nonCompliant = false;
    protected bool $checkSignature = true;
    protected array $params = [];
    protected string|null $previousSnapshotDir = null;
    protected string|null $sslCertificate = null;
    protected string|null $sslPrivateKey = null;
    protected string|null $sslCaCertificate = null;
    protected array $expectedFiles = [];
    protected int $totalSize = 0;
    protected array $stats = [];
    protected $taskLogSubStepController;

    public function __construct(int $taskId, string $workingDir)
    {
        $this->taskId = $taskId;
        $this->workingDir = $workingDir;
        $this->tempDir = TEMP_DIR . '/mirror-task-' . $taskId;
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);
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
     *  Set source repository definition (URL, SSL authentication...)
     */
    public function setSource(array $definition): void
    {
        if (empty($definition['url'])) {
            throw new Exception('Source repository URL is empty');
        }

        $this->url = rtrim($definition['url'], '/');
        $this->nonCompliant = ($definition['non-compliant'] ?? 'false') == 'true';

        // SSL authentication material is written to temporary files, as expected by curl
        $sslFiles = [
            'certificate'    => 'sslCertificate',
            'private-key'    => 'sslPrivateKey',
            'ca-certificate' => 'sslCaCertificate'
        ];

        foreach ($sslFiles as $key => $property) {
            if (empty($definition['ssl-authentication'][$key])) {
                continue;
            }

            $this->createTempDir();

            if (!file_put_contents($this->tempDir . '/ssl-' . $key, $definition['ssl-authentication'][$key])) {
                throw new Exception('Could not write SSL ' . $key . ' to temporary file');
            }

            $this->$property = $this->tempDir . '/ssl-' . $key;
        }
    }

    /**
     *  Set mirror parameters (dist, components, releasever, arch...)
     */
    public function setParams(array $params): void
    {
        $this->params = $params;
    }

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
     *
     *  Return the relative paths of the files that are present after retrieval
     */
    protected function fetch(array $files, bool $optional = false, bool $force = false, bool $verifyExisting = false): array
    {
        $queue = [];
        $present = [];
        $this->stats = ['total' => 0, 'processed' => 0, 'downloaded' => 0, 'downloadedBytes' => 0, 'linked' => 0, 'existing' => 0];

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
                    $this->progress();
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
                        $this->progress();
                        continue;
                    }
                }
            }

            $queue[] = $file;
        }

        $missing = $this->download($queue, $optional);

        foreach ($queue as $file) {
            if (!in_array($file['path'], $missing)) {
                $present[] = $file['path'];
                $this->addExpected($file['path']);
            }
        }

        return $present;
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
     *  Download files in parallel, verify them and move them to their final path
     *  Return the relative paths of the optional files that were not found (404)
     */
    private function download(array $files, bool $optional): array
    {
        $missing = [];
        $pending = array_values($files);

        for ($round = 0; $round <= self::RETRIES and !empty($pending); $round++) {
            if ($round > 0) {
                $this->taskLogSubStepController->output('Retrying ' . count($pending) . ' failed download(s) (' . $round . '/' . self::RETRIES . ')', 'warning');
            }

            $failed = [];
            $active = [];
            $index = 0;
            $multiHandle = curl_multi_init();

            while ($index < count($pending) or !empty($active)) {
                // Fill the download slots
                while (count($active) < self::PARALLEL_DOWNLOADS and $index < count($pending)) {
                    $file = $pending[$index++];
                    $target = $this->workingDir . '/' . $file['path'];

                    $this->createParentDir($target);

                    $fp = fopen($target . '.part', 'w');

                    if ($fp === false) {
                        throw new Exception('Could not open ' . $target . '.part for writing');
                    }

                    $handle = $this->curlInit($file['url'], $fp);
                    curl_multi_add_handle($multiHandle, $handle);
                    $active[spl_object_id($handle)] = ['handle' => $handle, 'fp' => $fp, 'file' => $file];
                }

                do {
                    $status = curl_multi_exec($multiHandle, $running);
                } while ($status == CURLM_CALL_MULTI_PERFORM);

                if ($status != CURLM_OK) {
                    throw new Exception('Download error: ' . curl_multi_strerror($status));
                }

                if (curl_multi_select($multiHandle, 1.0) == -1) {
                    usleep(100000);
                }

                // Process finished downloads
                while ($info = curl_multi_info_read($multiHandle)) {
                    $id = spl_object_id($info['handle']);
                    $file = $active[$id]['file'];
                    $target = $this->workingDir . '/' . $file['path'];
                    $httpCode = curl_getinfo($info['handle'], CURLINFO_RESPONSE_CODE);

                    fclose($active[$id]['fp']);
                    curl_multi_remove_handle($multiHandle, $info['handle']);
                    curl_close($info['handle']);
                    unset($active[$id]);

                    if ($info['result'] == CURLE_OK and $this->verify($target . '.part', $file['size'], $file['hash'], $file['algo'])) {
                        if (!rename($target . '.part', $target)) {
                            throw new Exception('Could not rename ' . $target . '.part');
                        }

                        $this->stats['downloaded']++;
                        $this->stats['downloadedBytes'] += filesize($target);
                        $this->progress();
                        continue;
                    }

                    unlink($target . '.part');

                    if ($optional and $httpCode == 404) {
                        $missing[] = $file['path'];
                        $this->progress();
                        continue;
                    }

                    if ($info['result'] != CURLE_OK) {
                        $file['error'] = curl_strerror($info['result']) . ($httpCode > 0 ? ' (HTTP ' . $httpCode . ')' : '');
                    } else {
                        $file['error'] = 'size or checksum mismatch';
                    }

                    $failed[] = $file;
                }
            }

            curl_multi_close($multiHandle);

            $pending = $failed;
        }

        if (!empty($pending)) {
            $message = count($pending) . ' file(s) could not be retrieved:';

            foreach (array_slice($pending, 0, 10) as $file) {
                $message .= '<br> 🠶 <span class="copy wordbreakall">' . $file['url'] . '</span>: ' . $file['error'];
            }

            throw new Exception($message);
        }

        return $missing;
    }

    /**
     *  Download a single file without verification, return false if it could not be downloaded
     */
    protected function downloadFile(string $url, string $savePath): bool
    {
        $this->createParentDir($savePath);

        $fp = fopen($savePath, 'w');

        if ($fp === false) {
            throw new Exception('Could not open ' . $savePath . ' for writing');
        }

        $handle = $this->curlInit($url, $fp);
        $result = curl_exec($handle);
        curl_close($handle);
        fclose($fp);

        if ($result === false) {
            unlink($savePath);
            return false;
        }

        return true;
    }

    /**
     *  Initialize a curl handle writing to the specified file pointer
     */
    private function curlInit(string $url, $fp)
    {
        $handle = curl_init();

        curl_setopt($handle, CURLOPT_URL, $url);
        curl_setopt($handle, CURLOPT_FILE, $fp);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 15);
        curl_setopt($handle, CURLOPT_TIMEOUT, MIRRORING_PACKAGE_DOWNLOAD_TIMEOUT);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        // Use http version to 1.1 to avoid curl error 16 (fix https://github.com/lbr38/repomanager/issues/127)
        curl_setopt($handle, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($handle, CURLOPT_USERAGENT, 'Repomanager (+https://github.com/lbr38/repomanager)');

        if (!empty(PROXY)) {
            curl_setopt($handle, CURLOPT_PROXY, PROXY);
        }
        if (!empty($this->sslCertificate)) {
            curl_setopt($handle, CURLOPT_SSLCERT, $this->sslCertificate);
        }
        if (!empty($this->sslPrivateKey)) {
            curl_setopt($handle, CURLOPT_SSLKEY, $this->sslPrivateKey);
        }
        if (!empty($this->sslCaCertificate)) {
            curl_setopt($handle, CURLOPT_CAINFO, $this->sslCaCertificate);
        }

        return $handle;
    }

    /**
     *  Return true if an existing file matches the expected size (and checksum if required)
     */
    private function matches(string $path, array $file, bool $verifyChecksum): bool
    {
        if ($verifyChecksum) {
            return $this->verify($path, $file['size'], $file['hash'], $file['algo']);
        }

        // Without known size, an existing file cannot be trusted
        if (is_null($file['size'])) {
            return false;
        }

        return filesize($path) == $file['size'];
    }

    /**
     *  Verify file size and checksum, if known
     */
    protected function verify(string $path, int|null $size, string|null $hash, string|null $algo): bool
    {
        if (!is_null($size) and filesize($path) != $size) {
            return false;
        }

        if (!empty($hash) and !empty($algo)) {
            return hash_equals(strtolower($hash), hash_file($algo, $path));
        }

        return true;
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
            throw new Exception('No GPG key could verify the signature of <code>' . basename($signatureFile) . '</code>. Please check that you have imported the GPG key used to sign the repository.<br><pre class="codeblock">' . $output . '</pre>');
        }

        if ($exitCode != 0) {
            throw new Exception('Invalid GPG signature of <code>' . basename($signatureFile) . '</code>:<br><pre class="codeblock">' . $output . '</pre>');
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
     *  Output download progress
     */
    private function progress(): void
    {
        $this->stats['processed']++;

        // Only output progress periodically, the final summary is given by fetchSummary()
        if ($this->stats['processed'] % self::PROGRESS_STEP != 0 or $this->stats['processed'] == $this->stats['total']) {
            return;
        }

        $this->taskLogSubStepController->output($this->stats['processed'] . ' / ' . $this->stats['total'] . ' (' . $this->fetchSummary() . ')', 'note');
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

    private function createParentDir(string $path): void
    {
        $dir = dirname($path);

        if (!is_dir($dir)) {
            if (!mkdir($dir, 0770, true) and !is_dir($dir)) {
                throw new Exception('Could not create directory ' . $dir);
            }
        }
    }

    private function createTempDir(): void
    {
        if (!is_dir($this->tempDir)) {
            if (!mkdir($this->tempDir, 0770, true)) {
                throw new Exception('Could not create temporary directory ' . $this->tempDir);
            }
        }
    }
}
