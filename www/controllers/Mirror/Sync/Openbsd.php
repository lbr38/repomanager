<?php

namespace Controllers\Mirror\Sync;

use Controllers\Utils\Signify;
use Controllers\Utils\Validate;
use Exception;

/**
 *  Mirroring of OpenBSD releases: packages, binary patches (syspatch), install sets and sources
 *
 *  The source URL is the root of an OpenBSD mirror (e.g. https://cdn.openbsd.org/pub/OpenBSD). A mirror is a subset of its tree,
 *  with the same layout, so that clients only set the mirror URL in /etc/installurl, as for an official mirror:
 *   - <version>/                          sources and release files, signed with the base key
 *   - <version>/<platform>/               install sets, used by sysupgrade and network installs, signed with the base key
 *   - <version>/packages/<arch>/          packages, used by pkg_add, signed with the pkg key
 *   - <version>/packages-stable/<arch>/   updated packages of the release, used by pkg_add before packages/, signed with the pkg key
 *   - syspatch/<version>/<platform>/      binary patches of the base system, used by syspatch, signed with the syspatch key
 *  A mirror contains several versions: sysupgrade looks for the next version under the same URL.
 *  Packages directories are named after the architecture of the platform (arch -s, e.g. aarch64 for arm64).
 *
 *  Each directory contains SHA256.sig: the list of its files with their SHA256 checksum (the content of the SHA256 file), signed with
 *  signify. This list is the reference of the files to retrieve, index.txt (ls -lT output) gives their size when present.
 *
 *  pkg_add lists the packages of a HTTP repository from the directory page (links to the .tgz files): an index.html listing the files
 *  is generated in each packages directory, the web server returns it for the directory URL.
 *
 *  Snapshots (development version) are not supported: their packages are rebuilt with the same file names.
 *  Firmwares are not part of the OpenBSD mirrors: fw_update always retrieves them from firmware.openbsd.org.
 */
class Openbsd extends Sync
{
    // Contents that can be mirrored
    public const CONTENTS = ['packages', 'syspatch', 'sets', 'sources'];

    // Architecture of the packages of each platform (MACHINE_ARCH, arch -s), when different from the platform name
    public const PACKAGE_ARCHS = [
        'arm64'    => 'aarch64',
        'armv7'    => 'arm',
        'landisk'  => 'sh',
        'loongson' => 'mips64el',
        'luna88k'  => 'm88k',
        'macppc'   => 'powerpc',
        'octeon'   => 'mips64'
    ];

    // Public keys of the source repository (key name => content), used to verify the signatures
    private array $keys = [];

    // Mirrored directories (relative path) and the checksum of their SHA256.sig file, to detect upstream changes
    private array $signatures = [];

    /**
     *  Set source repository definition, with its public keys
     */
    public function setSource(array $definition): void
    {
        parent::setSource($definition);

        $this->keys = [];

        foreach ($definition['keys'] ?? [] as $key) {
            if (!empty($key['name']) and !empty($key['content'])) {
                $this->keys[$key['name']] = $key['content'];
            }
        }
    }

    /**
     *  Retrieve the signed files list of each directory of the selected versions, platforms and contents, then the files
     */
    protected function syncFiles(): void
    {
        $versions = $this->params['versions'] ?? [];
        $contents = $this->params['content'] ?? [];

        if (empty($versions)) {
            throw new Exception('At least one version must be specified');
        }

        if (empty($this->params['arch'])) {
            throw new Exception('At least one architecture must be specified');
        }

        if (empty($contents)) {
            throw new Exception('At least one content must be specified');
        }

        $this->signatures = [];
        $packages = [];
        $files = [];

        foreach ($this->getDirectories($versions, $this->params['arch'], $contents) as $dir) {
            $this->taskLogSubStepController->new('retrieve-metadata-' . str_replace(['/', '.'], '-', $dir['path']), 'RETRIEVING FILES LIST (' . $dir['path'] . ')', 'From ' . $this->url . '/' . $dir['path']);

            $dirFiles = $this->retrieveFilesList($dir);

            if ($dirFiles === null) {
                continue;
            }

            if ($dir['packages']) {
                array_push($packages, ...$dirFiles);
            } else {
                array_push($files, ...$dirFiles);
            }
        }

        if (empty($packages) and empty($files)) {
            throw new Exception('No file found in the source repository. Please check the source repository URL, versions and architectures.');
        }

        if (!empty($packages)) {
            $this->fetchPackages($packages);
        }

        if (!empty($files)) {
            $this->fetchFiles($files);
        }
    }

    /**
     *  Return true if the files list of any of the mirrored directories has changed upstream
     */
    protected function upstreamChanged(): bool
    {
        $tempFile = $this->tempDir . '/SHA256.sig.check';

        foreach ($this->signatures as $path => $hash) {
            if (!$this->downloader->downloadFile($this->url . '/' . $path . '/SHA256.sig', $tempFile)) {
                return true;
            }

            $changed = hash_file('sha256', $tempFile) !== $hash;

            unlink($tempFile);

            if ($changed) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Return the directories to mirror for the specified versions, platforms and contents
     *  Each directory: relative path, content, whether it is a packages directory and whether it is optional (a missing optional
     *  directory is not an error, with a note or a warning)
     */
    private function getDirectories(array $versions, array $platforms, array $contents): array
    {
        $dirs = [];

        foreach ($versions as $version) {
            if (in_array('sources', $contents)) {
                $dirs[] = ['path' => $version, 'packages' => false, 'optional' => false];
            }

            foreach ($platforms as $platform) {
                $arch = self::PACKAGE_ARCHS[$platform] ?? $platform;

                if (in_array('sets', $contents)) {
                    $dirs[] = ['path' => $version . '/' . $platform, 'packages' => false, 'optional' => false];
                }

                // Not every platform has packages, packages updates (packages-stable) and binary patches
                if (in_array('packages', $contents)) {
                    $dirs[] = ['path' => $version . '/packages/' . $arch, 'packages' => true, 'optional' => true, 'missing' => 'No packages for ' . $platform . ' in OpenBSD ' . $version, 'level' => 'warning'];
                    $dirs[] = ['path' => $version . '/packages-stable/' . $arch, 'packages' => true, 'optional' => true, 'missing' => 'No packages updates (packages-stable) for ' . $platform . ' in OpenBSD ' . $version, 'level' => 'note'];
                }

                if (in_array('syspatch', $contents)) {
                    $dirs[] = ['path' => 'syspatch/' . $version . '/' . $platform, 'packages' => false, 'optional' => true, 'missing' => 'No binary patches (syspatch) for ' . $platform . ' in OpenBSD ' . $version, 'level' => 'note'];
                }
            }
        }

        return $dirs;
    }

    /**
     *  Retrieve the metadata of a directory (SHA256.sig, SHA256 and index.txt) and verify the signature
     *  Return the files to retrieve, or null if the directory is optional and does not exist upstream
     */
    private function retrieveFilesList(array $dir): array|null
    {
        $path = $dir['path'];
        $url = $this->url . '/' . $path;

        // Metadata files have no known checksum, they are always downloaded
        $present = $this->fetch([$this->entry($path . '/SHA256.sig', $url . '/SHA256.sig')], true, true);

        if (!in_array($path . '/SHA256.sig', $present)) {
            if (!$dir['optional']) {
                throw new Exception('No <code>SHA256.sig</code> found in ' . htmlspecialchars($url) . '. Please check the source repository URL, versions and architectures.');
            }

            $this->taskLogSubStepController->output($dir['missing'], $dir['level']);
            $this->taskLogSubStepController->completed();
            return null;
        }

        $signatureFile = $this->workingDir . '/' . $path . '/SHA256.sig';
        $this->signatures[$path] = hash_file('sha256', $signatureFile);

        $list = $this->readFilesList($signatureFile);

        $present = $this->fetch([
            $this->entry($path . '/SHA256', $url . '/SHA256'),
            $this->entry($path . '/index.txt', $url . '/index.txt')
        ], true, true);

        // The SHA256 file is the signed list itself
        if (in_array($path . '/SHA256', $present) and file_get_contents($this->workingDir . '/' . $path . '/SHA256') !== $list) {
            throw new Exception('<code>SHA256</code> of ' . htmlspecialchars($path) . ' does not match the signed files list of <code>SHA256.sig</code>');
        }

        $sizes = in_array($path . '/index.txt', $present) ? $this->parseIndex($this->workingDir . '/' . $path . '/index.txt') : [];

        $files = [];

        foreach ($this->parseFilesList($path, $list) as $name => $hash) {
            $files[] = $this->entry($path . '/' . $name, $url . '/' . rawurlencode($name), $sizes[$name] ?? null, $hash, 'sha256');
        }

        // pkg_add lists the packages from the directory page
        if ($dir['packages']) {
            $this->writeDirectoryIndex($path, array_merge(array_column($files, 'path'), $present, [$path . '/SHA256.sig']));
        }

        $this->taskLogSubStepController->completed(count($files) . ' file(s) listed');

        return $files;
    }

    /**
     *  Return the files list of a SHA256.sig file (the signed message), after verifying its signature if required
     */
    private function readFilesList(string $signatureFile): string
    {
        $content = file_get_contents($signatureFile);

        if ($content === false) {
            throw new Exception('Could not read ' . $signatureFile);
        }

        if (!$this->checkSignature) {
            return Signify::readEmbedded($content)['message'];
        }

        $verified = Signify::verifyEmbedded($content, $this->keys);

        $this->taskLogSubStepController->output('Signature verified: ' . htmlspecialchars($verified['key']));

        return $verified['message'];
    }

    /**
     *  Parse a files list (SHA256 file format: 'SHA256 (<name>) = <checksum>'), return the files name and their checksum (hexadecimal)
     *  Checksums are in hexadecimal or in base64 (packages lists, sha256 -b)
     */
    private function parseFilesList(string $path, string $list): array
    {
        $files = [];

        foreach (explode("\n", $list) as $line) {
            if (trim($line) === '') {
                continue;
            }

            if (!preg_match('/^SHA256 \((.+)\) = ([A-Za-z0-9+\/=]+)$/', $line, $matches)) {
                throw new Exception('Invalid line in the files list of ' . htmlspecialchars($path) . ': ' . htmlspecialchars($line));
            }

            [, $name, $checksum] = $matches;

            if (str_contains($name, '/') or str_starts_with($name, '.') or !Validate::alphaNumericHyphen($name, ['.', '+', '@', '~', ':'])) {
                throw new Exception('Invalid file name in the files list of ' . htmlspecialchars($path) . ': ' . htmlspecialchars($name));
            }

            if (strlen($checksum) != 64 or !ctype_xdigit($checksum)) {
                $decoded = base64_decode($checksum, true);

                if ($decoded === false or strlen($decoded) != 32) {
                    throw new Exception('Invalid checksum of ' . htmlspecialchars($name) . ' in the files list of ' . htmlspecialchars($path));
                }

                $checksum = bin2hex($decoded);
            }

            $files[$name] = strtolower($checksum);
        }

        return $files;
    }

    /**
     *  Parse an index.txt file (ls -lT output), return the size of each file
     */
    private function parseIndex(string $path): array
    {
        $sizes = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            throw new Exception('Could not read ' . $path);
        }

        foreach ($lines as $line) {
            $fields = preg_split('/\s+/', trim($line));

            // Mode, links, owner, group, size, date (month, day, time, year) and name, the 'total' line is ignored
            if (count($fields) < 6 or !ctype_digit($fields[4])) {
                continue;
            }

            $sizes[end($fields)] = (int) $fields[4];
        }

        return $sizes;
    }

    /**
     *  Retrieve the files of the directories other than packages (install sets, binary patches, sources)
     *  Files without known size (directories without index.txt) can only be reused after verifying their checksum
     */
    private function fetchFiles(array $files): void
    {
        $this->taskLogSubStepController->new('retrieve-files', 'DOWNLOADING FILES', 'Install sets, binary patches and sources');

        if ($this->metadataOnly()) {
            $this->taskLogSubStepController->completed(count($files) . ' file(s) found, not retrieved (metadata only)', 'retrieve-files');
            return;
        }

        $summary = [];
        $withSize = array_filter($files, function ($file) {
            return !is_null($file['size']);
        });
        $withoutSize = array_filter($files, function ($file) {
            return is_null($file['size']);
        });

        foreach ([[$withSize, false], [$withoutSize, true]] as [$list, $verifyExisting]) {
            if (empty($list)) {
                continue;
            }

            $this->fetch($list, false, false, $verifyExisting, 'DOWNLOADING FILE');
            $summary[] = $this->fetchSummary();
        }

        $this->taskLogSubStepController->completed(count($files) . ' file(s): ' . implode(', ', $summary), 'retrieve-files');
    }

    /**
     *  Write the index.html page of a directory, listing its files as a web server directory listing does
     *  The page is generated, it is not a hard link to the previous snapshot: it is deleted first if it exists
     */
    private function writeDirectoryIndex(string $dir, array $paths): void
    {
        $title = 'Index of ' . htmlspecialchars($dir) . '/';
        $names = array_unique(array_map('basename', $paths));
        sort($names, SORT_STRING);

        $html = '<!DOCTYPE html>' . PHP_EOL;
        $html .= '<html>' . PHP_EOL . '<head><meta charset="utf-8"><title>' . $title . '</title></head>' . PHP_EOL;
        $html .= '<body>' . PHP_EOL . '<h1>' . $title . '</h1>' . PHP_EOL . '<pre>' . PHP_EOL . '<a href="../">../</a>' . PHP_EOL;

        foreach ($names as $name) {
            $html .= '<a href="' . htmlspecialchars(rawurlencode($name)) . '">' . htmlspecialchars($name) . '</a>' . PHP_EOL;
        }

        $html .= '</pre>' . PHP_EOL . '</body>' . PHP_EOL . '</html>' . PHP_EOL;

        $target = $this->workingDir . '/' . $dir . '/index.html';

        if (file_exists($target) and !unlink($target)) {
            throw new Exception('Could not delete ' . $target);
        }

        if (file_put_contents($target, $html) === false) {
            throw new Exception('Could not write ' . $target);
        }

        $this->addExpected($dir . '/index.html');
    }
}
