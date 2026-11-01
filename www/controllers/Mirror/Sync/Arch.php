<?php

namespace Controllers\Mirror\Sync;

use Controllers\Utils\Validate;
use Exception;

/**
 *  Mirroring of an Arch Linux (pacman) repository
 *
 *  The source URL contains $repo and/or $arch, as in a pacman mirrorlist (e.g. https://mirror/$repo/os/$arch).
 *  Each repository (core, extra...) of each arch is described by <url>/<repo>.db, a (compressed) tar archive containing
 *  a 'desc' file per package with its file name, size and checksum. <repo>.files also lists the content of the packages.
 *  Databases are optionally signed (<repo>.db.sig), packages are signed (<package>.sig, also embedded in the database).
 *
 *  Each repository of each arch is mirrored in <snapshot>/<repo>/os/<arch>/, whatever the source URL layout, so that
 *  clients always use: Server = <mirror URL>/$repo/os/$arch
 */
class Arch extends Sync
{
    // Mirrored repositories: relative directory, URL and database checksum (to detect upstream changes)
    private array $repos = [];

    /**
     *  Retrieve the databases, packages and packages signatures of each selected repository and architecture
     */
    protected function syncFiles(): void
    {
        if (empty($this->params['repos'])) {
            throw new Exception('At least one repository must be specified');
        }

        if (empty($this->params['arch'])) {
            throw new Exception('At least one architecture must be specified');
        }

        $this->repos = [];
        $packages = [];
        $signatures = [];

        foreach ($this->params['repos'] as $repoName) {
            foreach ($this->params['arch'] as $arch) {
                $repo = [
                    'name' => $repoName,
                    'arch' => $arch,
                    'dir'  => $repoName . '/os/' . $arch,
                    'url'  => str_replace(['$repo', '$arch'], [$repoName, $arch], $this->url)
                ];

                $label = ' (' . $repoName . '/' . $arch . ')';

                $this->taskLogSubStepController->new('retrieve-metadata-' . $repoName . '-' . $arch, 'RETRIEVING DATABASE' . $label, 'From ' . $repo['url']);

                $database = $this->retrieveDatabases($repo);

                $repo['dbHash'] = hash_file('sha256', $this->workingDir . '/' . $database);
                $this->repos[] = $repo;

                $this->taskLogSubStepController->new('parse-database-' . $repoName . '-' . $arch, 'PARSING PACKAGES LIST' . $label);

                [$repoPackages, $repoSignatures] = $this->parseDatabase($repo, $database);
                $packages = array_merge($packages, $repoPackages);
                $signatures = array_merge($signatures, $repoSignatures);

                $this->taskLogSubStepController->completed(count($repoPackages) . ' package(s) found');
            }
        }

        if (empty($packages)) {
            throw new Exception('No package found in the source repository');
        }

        $this->taskLogSubStepController->new('retrieve-packages', 'RETRIEVING PACKAGES');
        $this->fetch($packages);
        $this->taskLogSubStepController->completed($this->stats['total'] . ' package(s): ' . $this->fetchSummary());

        $this->taskLogSubStepController->new('retrieve-signatures', 'RETRIEVING PACKAGES SIGNATURES');
        $this->retrieveSignatures($signatures);
    }

    /**
     *  Return true if a database has changed upstream for any of the mirrored repositories
     */
    protected function upstreamChanged(): bool
    {
        foreach ($this->repos as $repo) {
            $tempFile = $this->tempDir . '/database.check';

            if (!$this->downloadFile($repo['url'] . '/' . $repo['name'] . '.db', $tempFile)) {
                return true;
            }

            $changed = hash_file('sha256', $tempFile) !== $repo['dbHash'];

            unlink($tempFile);

            if ($changed) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Retrieve the package and files databases of a repository and their signatures, return the package database relative path
     */
    private function retrieveDatabases(array $repo): string
    {
        $database = $repo['dir'] . '/' . $repo['name'] . '.db';
        $filesDatabase = $repo['dir'] . '/' . $repo['name'] . '.files';

        // Databases have no known checksum, they are always downloaded
        $present = $this->fetch([
            $this->entry($database, $repo['url'] . '/' . $repo['name'] . '.db'),
            $this->entry($database . '.sig', $repo['url'] . '/' . $repo['name'] . '.db.sig')
        ], true, true);

        if (!in_array($database, $present)) {
            throw new Exception('No <code>' . htmlspecialchars($repo['name']) . '.db</code> database found. Please check the source repository URL, repository and architecture.');
        }

        $this->verifyDatabase($database, in_array($database . '.sig', $present));

        /**
         *  The files database is updated with the package database: if the package database is unchanged since the
         *  previous snapshot, the files database is hard linked from it instead of being downloaded again (it can be large)
         */
        $previousDatabase = $this->previousSnapshotDir . '/' . $database;
        $previousFilesDatabase = $this->previousSnapshotDir . '/' . $filesDatabase;

        if (!empty($this->previousSnapshotDir) and is_file($previousDatabase) and is_file($previousFilesDatabase) and hash_file('sha256', $previousDatabase) === hash_file('sha256', $this->workingDir . '/' . $database)) {
            foreach ([$filesDatabase, $filesDatabase . '.sig'] as $path) {
                if (is_file($this->previousSnapshotDir . '/' . $path)) {
                    $this->link($this->previousSnapshotDir . '/' . $path, $this->workingDir . '/' . $path);
                    $this->addExpected($path);
                }
            }

            $this->taskLogSubStepController->completed('Database unchanged since previous snapshot');

            return $database;
        }

        // The files database is optional (only used by pacman -F)
        $present = $this->fetch([
            $this->entry($filesDatabase, $repo['url'] . '/' . $repo['name'] . '.files'),
            $this->entry($filesDatabase . '.sig', $repo['url'] . '/' . $repo['name'] . '.files.sig')
        ], true, true);

        if (!in_array($filesDatabase, $present)) {
            $this->taskLogSubStepController->output('No <code>' . htmlspecialchars($repo['name']) . '.files</code> database found, <code>pacman -F</code> will not be available on clients', 'warning');
        }

        $this->taskLogSubStepController->completed();

        return $database;
    }

    /**
     *  Check package database signature
     *  Most Arch Linux repositories (including official ones) do not sign their database (packages are signed instead and
     *  verified by pacman on clients), so a missing signature is only a note
     */
    private function verifyDatabase(string $database, bool $hasSignature): void
    {
        if (!$this->checkSignature) {
            return;
        }

        if (!$hasSignature) {
            $this->taskLogSubStepController->output('The database is not signed by the source repository (as for Arch Linux official repositories): packages signatures are checked by pacman on clients', 'note');
            return;
        }

        $signer = $this->checkGpgSignature($this->workingDir . '/' . $database . '.sig', $this->workingDir . '/' . $database);
        $this->taskLogSubStepController->output('Database signature verified' . (!empty($signer) ? ': ' . $signer : ''));
    }

    /**
     *  Parse a package database, return the list of packages and the list of their signatures to retrieve
     */
    private function parseDatabase(array $repo, string $database): array
    {
        $packages = [];
        $signatures = [];

        // GNU tar detects the compression (gzip, xz, zstd...) itself, only 'desc' files are needed
        $stream = popen('LC_ALL=C /usr/bin/tar -xOf ' . escapeshellarg($this->workingDir . '/' . $database) . ' --wildcards ' . escapeshellarg('*/desc') . ' 2>/dev/null', 'r');

        if ($stream === false) {
            throw new Exception('Could not open ' . $repo['name'] . '.db');
        }

        $addPackage = function (array $desc) use ($repo, &$packages, &$signatures) {
            if (empty($desc['FILENAME'][0])) {
                return;
            }

            $filename = $desc['FILENAME'][0];

            if (str_contains($filename, '/') or !Validate::alphaNumericHyphen($filename, ['.', '+', '@', '~', ':'])) {
                throw new Exception('Invalid package file name in ' . htmlspecialchars($repo['name']) . '.db: ' . htmlspecialchars($filename));
            }

            if (!empty($desc['SHA256SUM'][0])) {
                $hash = $desc['SHA256SUM'][0];
                $algo = 'sha256';
            } else {
                $hash = $desc['MD5SUM'][0] ?? null;
                $algo = is_null($hash) ? null : 'md5';
            }

            $packages[] = $this->entry($repo['dir'] . '/' . $filename, $repo['url'] . '/' . $filename, isset($desc['CSIZE'][0]) ? (int) $desc['CSIZE'][0] : null, $hash, $algo);

            // An embedded signature is the exact content of the signature file: its size and checksum are known
            $signature = $this->entry($repo['dir'] . '/' . $filename . '.sig', $repo['url'] . '/' . $filename . '.sig');

            if (!empty($desc['PGPSIG'][0])) {
                $content = base64_decode(implode('', $desc['PGPSIG']), true);

                if ($content !== false) {
                    $signature['size'] = strlen($content);
                    $signature['hash'] = hash('sha256', $content);
                    $signature['algo'] = 'sha256';
                }
            }

            $signature['package'] = end($packages);
            $signatures[] = $signature;
        };

        $desc = [];
        $key = null;

        while (($line = fgets($stream)) !== false) {
            $line = rtrim($line, "\r\n");

            // Each desc file starts with %FILENAME%
            if ($line === '%FILENAME%') {
                $addPackage($desc);
                $desc = [];
            }

            if (preg_match('/^%([A-Z0-9]+)%$/', $line, $matches)) {
                $key = $matches[1];
                $desc[$key] = [];
                continue;
            }

            if ($line === '') {
                $key = null;
                continue;
            }

            if (!is_null($key)) {
                $desc[$key][] = $line;
            }
        }

        $addPackage($desc);

        if (pclose($stream) != 0) {
            throw new Exception('Could not read ' . htmlspecialchars($repo['name']) . '.db, the database is invalid or uses an unsupported compression');
        }

        return [$packages, $signatures];
    }

    /**
     *  Retrieve packages signatures
     *  Signatures embedded in the database have a known size and checksum, they are retrieved like packages.
     *  Other signatures are optional (unsigned packages): they are only looked for when the package is new since the
     *  previous snapshot, to not request every missing signature again at each sync
     */
    private function retrieveSignatures(array $signatures): void
    {
        $known = [];
        $unknown = [];
        $reused = 0;

        foreach ($signatures as $signature) {
            if (!is_null($signature['size'])) {
                $known[] = $signature;
                continue;
            }

            // Already retrieved by a previous attempt
            if (is_file($this->workingDir . '/' . $signature['path'])) {
                $this->addExpected($signature['path']);
                continue;
            }

            if (!empty($this->previousSnapshotDir)) {
                $previousPackage = $this->previousSnapshotDir . '/' . $signature['package']['path'];

                // The package is unchanged since the previous snapshot: so is its signature, or its absence
                if (is_file($previousPackage) and filesize($previousPackage) == $signature['package']['size']) {
                    if (is_file($this->previousSnapshotDir . '/' . $signature['path'])) {
                        $this->link($this->previousSnapshotDir . '/' . $signature['path'], $this->workingDir . '/' . $signature['path']);
                        $this->addExpected($signature['path']);
                        $reused++;
                    }
                    continue;
                }
            }

            $unknown[] = $signature;
        }

        $summary = [];

        if (!empty($known)) {
            $this->fetch($known, true);
            $summary[] = $this->stats['total'] . ' signature(s) embedded in the database: ' . $this->fetchSummary();
        }

        if (!empty($unknown)) {
            $present = $this->fetch($unknown, true);
            $summary[] = count($unknown) . ' new package(s) without embedded signature: ' . count($present) . ' signature(s) found';
        }

        if ($reused > 0) {
            $summary[] = $reused . ' signature(s) unchanged since previous snapshot';
        }

        $this->taskLogSubStepController->completed(empty($summary) ? 'No signature to retrieve' : implode('<br>', $summary));
    }
}
