<?php

namespace Controllers\Mirror\Sync;

use Exception;

/**
 *  Mirroring of a deb repository
 *
 *  Standard repository: <url>/dists/<dist>/InRelease (or Release + Release.gpg), indices listed in the Release file,
 *  packages in <url>/pool/... as referenced by the 'Filename' field of the Packages indices
 *  Flat (non-compliant) repository: Release file and indices directly under <url>
 */
class Deb extends Sync
{
    // Checksum fields of Release / Packages files, strongest first
    private const RELEASE_CHECKSUMS = ['sha256' => 'sha256', 'sha1' => 'sha1', 'md5sum' => 'md5'];

    // Compression preference when choosing which variant of an index to parse
    private const INDEX_EXTENSIONS = ['', '.gz', '.xz', '.bz2', '.zst'];

    // Relative path of the Release file directory of the distribution being synced ('dists/<dist>', or '' for flat repositories)
    private string $metadataPath;

    // Release files of each distribution (InRelease, or Release), used to detect upstream changes: [['url' => ..., 'hash' => ...]]
    private array $rootFiles = [];

    protected function syncFiles(): void
    {
        $dists = $this->params['dists'] ?? [];
        $components = $this->params['components'] ?? [];

        if (str_contains($this->url, '$component')) {
            throw new Exception('The <code>$component</code> variable in the source repository URL is not supported for mirrors');
        }

        if (!$this->nonCompliant) {
            if (empty($dists)) {
                throw new Exception('At least one distribution must be specified');
            }
            if (empty($components)) {
                throw new Exception('At least one component must be specified');
            }
        }

        // The base URL would be different for each distribution
        if (str_contains($this->url, '$dist')) {
            if (count($dists) != 1) {
                throw new Exception('The <code>$dist</code> variable in the source repository URL requires exactly one distribution');
            }

            $this->url = str_replace('$dist', $dists[0], $this->url);
        }

        // A flat repository has a single Release file, at the root of the URL
        if ($this->nonCompliant) {
            $dists = [''];
        }

        $this->rootFiles = [];
        $presentIndices = [];

        foreach ($dists as $dist) {
            $this->metadataPath = $dist === '' ? '' : 'dists/' . $dist;
            $suffix = $dist === '' ? '' : ' (' . $dist . ')';

            // Release files
            $this->taskLogSubStepController->new('retrieve-release-' . $dist, 'RETRIEVING RELEASE FILE' . $suffix, 'From ' . $this->metadataUrl());

            $releaseFiles = [];
            foreach (['InRelease', 'Release', 'Release.gpg'] as $name) {
                $releaseFiles[] = $this->entry($this->metadataRelPath($name), $this->metadataUrl() . '/' . $name);
            }

            $present = $this->fetch($releaseFiles, true, true);
            $release = $this->verifyRelease($present);

            $this->taskLogSubStepController->completed(implode(', ', array_map('basename', $present)) . ' retrieved');

            // Indices (Packages, Sources, Contents, Translations...)
            $this->taskLogSubStepController->new('retrieve-indices-' . $dist, 'RETRIEVING INDICES' . $suffix, 'Packages lists, translations and contents of the selected components and architectures');

            [$indices, $byHash] = $this->parseRelease($release);

            if (empty($indices)) {
                throw new Exception('No index matching the selected component(s) and architecture(s) found in the Release file' . $suffix);
            }

            $distIndices = $this->fetch($indices, true, false, true);

            if ($byHash) {
                $this->linkByHash($indices, $distIndices);
            }

            $this->taskLogSubStepController->completed(count($distIndices) . ' index file(s): ' . $this->fetchSummary());

            $presentIndices = array_merge($presentIndices, $distIndices);
        }

        // Packages, all distributions share the same pool
        $this->taskLogSubStepController->new('parse-indices', 'PARSING PACKAGES LIST');

        $packages = $this->parsePackages($presentIndices);

        if (in_array('src', $this->params['arch'] ?? [])) {
            $packages = array_merge($packages, $this->parseSources($presentIndices));
        }

        if (empty($packages)) {
            throw new Exception('No package found matching the selected component(s) and architecture(s)');
        }

        $this->taskLogSubStepController->completed(count(array_unique(array_column($packages, 'path'))) . ' package(s) found');

        $this->taskLogSubStepController->new('retrieve-packages', 'RETRIEVING PACKAGES', 'From ' . $this->url);
        $this->fetch($packages);
        $this->taskLogSubStepController->completed($this->stats['total'] . ' package(s): ' . $this->fetchSummary());
    }

    /**
     *  Return true if the InRelease (or Release) file of any distribution has changed upstream
     */
    protected function upstreamChanged(): bool
    {
        foreach ($this->rootFiles as $rootFile) {
            $tempFile = $this->tempDir . '/release.check';

            if (!$this->downloadFile($rootFile['url'], $tempFile)) {
                return true;
            }

            $changed = hash_file('sha256', $tempFile) !== $rootFile['hash'];

            unlink($tempFile);

            if ($changed) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Check Release file signature and return the path of a file containing the verified Release content
     */
    private function verifyRelease(array $present): string
    {
        $inRelease = $this->metadataRelPath('InRelease');
        $release = $this->metadataRelPath('Release');
        $releaseGpg = $this->metadataRelPath('Release.gpg');

        $hasInRelease = in_array($inRelease, $present);
        $hasRelease = in_array($release, $present);
        $hasReleaseGpg = in_array($releaseGpg, $present);

        if (!$hasInRelease and !$hasRelease) {
            throw new Exception('No <code>InRelease</code> or <code>Release</code> file found. Please check the source repository URL and distribution.');
        }

        // InRelease is preferred by apt, it is used to detect upstream changes
        $rootFile = $hasInRelease ? 'InRelease' : 'Release';
        $this->rootFiles[] = [
            'url'  => $this->metadataUrl() . '/' . $rootFile,
            'hash' => hash_file('sha256', $this->workingDir . '/' . $this->metadataRelPath($rootFile))
        ];

        $verifiedRelease = $this->tempDir . '/Release.verified';

        if (!$this->checkSignature) {
            if ($hasRelease) {
                return $this->workingDir . '/' . $release;
            }

            $this->extractClearsigned($this->workingDir . '/' . $inRelease, $verifiedRelease);

            return $verifiedRelease;
        }

        // Only the signed content of InRelease is kept, to never parse unsigned data
        if ($hasInRelease) {
            try {
                $signer = $this->checkGpgSignature($this->workingDir . '/' . $inRelease, '', $verifiedRelease);
                $this->taskLogSubStepController->output('InRelease signature verified' . (!empty($signer) ? ': ' . $signer : ''));

                return $verifiedRelease;
            } catch (Exception $e) {
                if (!$hasRelease or !$hasReleaseGpg) {
                    throw $e;
                }

                $this->taskLogSubStepController->output('InRelease: ' . $e->getMessage(), 'warning');
            }
        }

        if ($hasRelease and $hasReleaseGpg) {
            $signer = $this->checkGpgSignature($this->workingDir . '/' . $releaseGpg, $this->workingDir . '/' . $release);
            $this->taskLogSubStepController->output('Release signature verified' . (!empty($signer) ? ': ' . $signer : ''));

            return $this->workingDir . '/' . $release;
        }

        throw new Exception('The source repository is not signed (no <code>InRelease</code> or <code>Release.gpg</code> file found). Disable GPG signature check to mirror it anyway.');
    }

    /**
     *  Extract the content of a clearsigned file (used when signature check is disabled)
     */
    private function extractClearsigned(string $file, string $output): void
    {
        $content = file_get_contents($file);

        if (!preg_match('/-----BEGIN PGP SIGNED MESSAGE-----\r?\n(?:[^\r\n]+\r?\n)*\r?\n(.*?)\r?\n-----BEGIN PGP SIGNATURE-----/s', $content, $matches)) {
            throw new Exception('Could not extract content from <code>InRelease</code> file');
        }

        // Remove dash-escaping
        if (!file_put_contents($output, preg_replace('/^- /m', '', $matches[1]) . "\n")) {
            throw new Exception('Could not write ' . $output);
        }
    }

    /**
     *  Parse Release file, return the list of wanted indices and whether by-hash is enabled
     */
    private function parseRelease(string $release): array
    {
        $fields = [];

        $this->parseStanzas($release, function ($stanza) use (&$fields) {
            if (empty($fields)) {
                $fields = $stanza;
            }
        });

        $indices = [];

        foreach (self::RELEASE_CHECKSUMS as $field => $algo) {
            if (empty($fields[$field])) {
                continue;
            }

            foreach (explode("\n", $fields[$field]) as $line) {
                $parts = preg_split('/\s+/', trim($line));

                if (count($parts) != 3) {
                    continue;
                }

                [$hash, $size, $path] = $parts;

                $path = $this->normalizePath($path);

                if ($path === '' or !$this->isWantedIndex($path)) {
                    continue;
                }

                $indices[] = $this->entry($this->metadataRelPath($path), $this->metadataUrl() . '/' . $path, (int) $size, $hash, $algo);
            }

            // Only the strongest checksum available is used
            break;
        }

        if (empty($indices) and empty(array_intersect_key($fields, self::RELEASE_CHECKSUMS))) {
            throw new Exception('No checksum found in the Release file');
        }

        $byHash = strtolower($fields['acquire-by-hash'] ?? '') == 'yes';

        return [$indices, $byHash];
    }

    /**
     *  Return true if an index (path relative to the Release file) matches the selected components and archs
     */
    private function isWantedIndex(string $path): bool
    {
        $segments = explode('/', $path);
        $filename = end($segments);

        // Installer images and udebs are not mirrored
        if (in_array('debian-installer', $segments) or str_contains($filename, '-udeb-')) {
            return false;
        }

        // Standard repository: first directory is the component
        if (!$this->nonCompliant and count($segments) > 1 and !in_array($segments[0], $this->params['components'])) {
            return false;
        }

        foreach ($segments as $segment) {
            if (preg_match('/^binary-(.+)$/', $segment, $matches)) {
                return $this->isWantedArch($matches[1]);
            }

            if ($segment == 'source') {
                return $this->isWantedArch('source');
            }
        }

        // Arch specific files: Contents-<arch>, Commands-<arch>, Components-<arch>.yml, Sources
        if (preg_match('/^(?:Contents|Commands|Components)-([a-z0-9]+)(?:\.|$)/', $filename, $matches)) {
            return $this->isWantedArch($matches[1]);
        }

        if (preg_match('/^Sources(?:\.|$)/', $filename)) {
            return $this->isWantedArch('source');
        }

        // Other files (Release, i18n, dep11 icons...)
        return true;
    }

    private function isWantedArch(string $arch): bool
    {
        if ($arch == 'all') {
            return true;
        }

        if ($arch == 'source') {
            return in_array('src', $this->params['arch'] ?? []);
        }

        return in_array($arch, $this->params['arch'] ?? []);
    }

    /**
     *  Create by-hash hard links (<dir>/by-hash/SHA256/<checksum>) for retrieved indices, as published upstream
     */
    private function linkByHash(array $indices, array $presentIndices): void
    {
        foreach ($indices as $index) {
            if ($index['algo'] != 'sha256' or !in_array($index['path'], $presentIndices)) {
                continue;
            }

            $byHashPath = $this->normalizePath(dirname($index['path']) . '/by-hash/SHA256/' . $index['hash']);

            $this->link($this->workingDir . '/' . $index['path'], $this->workingDir . '/' . $byHashPath);
            $this->addExpected($byHashPath);
        }
    }

    /**
     *  Parse Packages indices and return the list of packages to retrieve
     */
    private function parsePackages(array $presentIndices): array
    {
        $packages = [];

        foreach ($this->selectIndices($presentIndices, 'Packages') as $index) {
            $countBefore = count($packages);

            $this->parseStanzas($this->workingDir . '/' . $index, function ($stanza) use (&$packages) {
                if (empty($stanza['filename'])) {
                    return;
                }

                [$hash, $algo] = $this->stanzaChecksum($stanza);
                $path = $this->normalizePath($stanza['filename']);

                $packages[] = $this->entry($path, $this->url . '/' . $path, isset($stanza['size']) ? (int) $stanza['size'] : null, $hash, $algo);
            });

            $this->taskLogSubStepController->output($this->indexLabel($index) . ': ' . (count($packages) - $countBefore) . ' package(s)');
        }

        return $packages;
    }

    /**
     *  Parse Sources indices and return the list of source files to retrieve
     */
    private function parseSources(array $presentIndices): array
    {
        $files = [];

        foreach ($this->selectIndices($presentIndices, 'Sources') as $index) {
            $countBefore = count($files);

            $this->parseStanzas($this->workingDir . '/' . $index, function ($stanza) use (&$files) {
                if (!isset($stanza['directory'])) {
                    return;
                }

                // Checksums-Sha256 lists files with their SHA256, Files lists them with their MD5
                foreach (['checksums-sha256' => 'sha256', 'files' => 'md5'] as $field => $algo) {
                    if (empty($stanza[$field])) {
                        continue;
                    }

                    foreach (explode("\n", $stanza[$field]) as $line) {
                        $parts = preg_split('/\s+/', trim($line));

                        if (count($parts) != 3) {
                            continue;
                        }

                        $path = $this->normalizePath($stanza['directory'] . '/' . $parts[2]);
                        $files[] = $this->entry($path, $this->url . '/' . $path, (int) $parts[1], $parts[0], $algo);
                    }

                    break;
                }
            });

            $this->taskLogSubStepController->output($this->indexLabel($index) . ': ' . (count($files) - $countBefore) . ' source file(s)');
        }

        return $files;
    }

    /**
     *  Return a short label of an index for the task log, e.g. 'bookworm/main/binary-amd64'
     */
    private function indexLabel(string $index): string
    {
        $label = dirname($index);

        // Keep the distribution in the label, as a mirror can contain several distributions
        if (str_starts_with($label, 'dists/')) {
            $label = substr($label, strlen('dists/'));
        }

        return $label == '.' ? basename($index) : $label;
    }

    /**
     *  Among retrieved indices, select one variant (uncompressed, .gz, .xz...) of each index with the specified name
     */
    private function selectIndices(array $presentIndices, string $name): array
    {
        $selected = [];
        $directories = [];

        foreach ($presentIndices as $path) {
            foreach (self::INDEX_EXTENSIONS as $extension) {
                if (basename($path) == $name . $extension) {
                    $directories[dirname($path)] = true;
                }
            }
        }

        foreach (array_keys($directories) as $directory) {
            $prefix = ($directory == '.' ? '' : $directory . '/') . $name;

            foreach (self::INDEX_EXTENSIONS as $extension) {
                if (in_array($prefix . $extension, $presentIndices)) {
                    $selected[] = $prefix . $extension;
                    break;
                }
            }
        }

        return $selected;
    }

    /**
     *  Return the strongest checksum of a package stanza
     */
    private function stanzaChecksum(array $stanza): array
    {
        foreach (self::RELEASE_CHECKSUMS as $field => $algo) {
            if (!empty($stanza[$field])) {
                return [$stanza[$field], $algo];
            }
        }

        return [null, null];
    }

    private function entry(string $path, string $url, int|null $size = null, string|null $hash = null, string|null $algo = null): array
    {
        return ['path' => $path, 'url' => $url, 'size' => $size, 'hash' => $hash, 'algo' => $algo];
    }

    private function metadataUrl(): string
    {
        return $this->metadataPath === '' ? $this->url : $this->url . '/' . $this->metadataPath;
    }

    private function metadataRelPath(string $path): string
    {
        return $this->metadataPath === '' ? $path : $this->metadataPath . '/' . $path;
    }
}
