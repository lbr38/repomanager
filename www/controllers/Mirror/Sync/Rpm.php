<?php

namespace Controllers\Mirror\Sync;

use SimpleXMLElement;
use XMLReader;
use DOMElement;
use Exception;

/**
 *  Mirroring of a rpm repository
 *
 *  Each repository is described by <url>/repodata/repomd.xml (optionally signed with repomd.xml.asc),
 *  which lists metadata files (primary, filelists, other, comps, updateinfo, modules...) with their checksum.
 *  Packages are listed in the primary metadata file with their location and checksum.
 *
 *  If the source URL contains $basearch, one repository per selected arch is mirrored in <snapshot>/<arch>/,
 *  otherwise the repository is mirrored as a whole in <snapshot>/
 */
class Rpm extends Sync
{
    // Checksum types used in repomd.xml and primary.xml
    private const CHECKSUMS = ['sha' => 'sha1', 'sha1' => 'sha1', 'sha224' => 'sha224', 'sha256' => 'sha256', 'sha384' => 'sha384', 'sha512' => 'sha512', 'md5' => 'md5'];

    // Mirrored repositories: relative directory, URL and repomd.xml checksum (to detect upstream changes)
    private array $repos = [];

    protected function syncFiles(): void
    {
        $releasever = $this->params['releasever'] ?? '';
        $url = str_replace('$releasever', $releasever, $this->url);
        $repos = [];

        $skippedNoarch = false;

        // One repository per arch
        if (str_contains($url, '$basearch')) {
            foreach ($this->params['arch'] ?? [] as $arch) {
                // There is no noarch repository, noarch packages are published in each arch repository
                if ($arch == 'noarch') {
                    $skippedNoarch = true;
                    continue;
                }

                // Source packages are usually published in a SRPMS directory
                $directory = $arch == 'src' ? 'SRPMS' : $arch;
                $repos[] = ['dir' => $directory, 'url' => str_replace('$basearch', $directory, $url)];
            }

            if (empty($repos)) {
                throw new Exception('noarch packages are published in each architecture repository, please select at least one architecture (e.g. x86_64)');
            }

        // Single repository
        } else {
            $repos[] = ['dir' => '', 'url' => $url];
        }

        $this->repos = [];
        $packages = [];

        foreach ($repos as $index => $repo) {
            $suffix = $repo['dir'] !== '' ? ' (' . $repo['dir'] . ')' : '';

            $this->taskLogSubStepController->new('retrieve-metadata-' . $repo['dir'], 'RETRIEVING METADATA' . $suffix, 'From ' . $repo['url']);

            if ($repo['dir'] === '' and count($this->params['arch'] ?? []) > 0) {
                $this->taskLogSubStepController->output('The source URL does not contain <code>$basearch</code>: the repository is mirrored as a whole, whatever the selected architectures', 'note');
            }

            if ($index == 0 and $skippedNoarch) {
                $this->taskLogSubStepController->output('noarch packages are included in each architecture repository', 'note');
            }

            // repomd.xml and its signature
            $repomdFiles = [];
            foreach (['repomd.xml', 'repomd.xml.asc', 'repomd.xml.key'] as $name) {
                $repomdFiles[] = $this->entry($this->relPath($repo['dir'], 'repodata/' . $name), $repo['url'] . '/repodata/' . $name);
            }

            $present = $this->fetch($repomdFiles, true, true);
            $repomd = $this->relPath($repo['dir'], 'repodata/repomd.xml');

            // A missing arch is not an error when several archs are mirrored, as long as one of them exists
            if (!in_array($repomd, $present)) {
                if (count($repos) > 1) {
                    $this->taskLogSubStepController->warning('No repository found for this architecture (no repodata/repomd.xml file), skipping');
                    continue;
                }

                throw new Exception('No <code>repodata/repomd.xml</code> file found. Please check the source repository URL and release version.');
            }

            $this->verifyRepomd($repomd, in_array($repomd . '.asc', $present));

            $repo['repomdHash'] = hash_file('sha256', $this->workingDir . '/' . $repomd);
            $this->repos[] = $repo;

            // Metadata files listed in repomd.xml
            [$metadata, $primary] = $this->parseRepomd($repo);

            $this->fetch($metadata, false, false, true);

            $this->taskLogSubStepController->completed(count($metadata) . ' metadata file(s): ' . implode(', ', array_unique(array_column($metadata, 'type'))));

            // Packages listed in primary
            $this->taskLogSubStepController->new('parse-primary-' . $repo['dir'], 'PARSING PACKAGES LIST' . $suffix);

            if (is_null($primary)) {
                throw new Exception('No primary metadata file found in repomd.xml');
            }

            $repoPackages = $this->parsePrimary($repo, $primary);
            $packages = array_merge($packages, $repoPackages);

            $this->taskLogSubStepController->completed(count($repoPackages) . ' package(s) found');
        }

        if (empty($this->repos)) {
            throw new Exception('No repository found for any of the selected architectures');
        }

        if (empty($packages)) {
            throw new Exception('No package found in the source repository');
        }

        $this->taskLogSubStepController->new('retrieve-packages', 'RETRIEVING PACKAGES');
        $this->fetch($packages);
        $this->taskLogSubStepController->completed($this->stats['total'] . ' package(s): ' . $this->fetchSummary());
    }

    /**
     *  Return true if repomd.xml has changed upstream for any of the mirrored repositories
     */
    protected function upstreamChanged(): bool
    {
        foreach ($this->repos as $repo) {
            $tempFile = $this->tempDir . '/repomd.xml.check';

            if (!$this->downloadFile($repo['url'] . '/repodata/repomd.xml', $tempFile)) {
                return true;
            }

            $changed = hash_file('sha256', $tempFile) !== $repo['repomdHash'];

            unlink($tempFile);

            if ($changed) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Check repomd.xml signature
     *  Most rpm repositories do not sign repomd.xml (packages are signed instead and verified by clients with gpgcheck=1),
     *  so a missing signature is only a warning
     */
    private function verifyRepomd(string $repomd, bool $hasSignature): void
    {
        if (!$this->checkSignature) {
            return;
        }

        if (!$hasSignature) {
            $this->taskLogSubStepController->output('repomd.xml is not signed by the source repository: packages signatures are checked by clients (gpgcheck=1)', 'warning');
            return;
        }

        $signer = $this->checkGpgSignature($this->workingDir . '/' . $repomd . '.asc', $this->workingDir . '/' . $repomd);
        $this->taskLogSubStepController->output('repomd.xml signature verified' . (!empty($signer) ? ': ' . $signer : ''));
    }

    /**
     *  Parse repomd.xml, return the list of metadata files and the primary metadata file entry
     */
    private function parseRepomd(array $repo): array
    {
        try {
            $xml = new SimpleXMLElement(file_get_contents($this->workingDir . '/' . $this->relPath($repo['dir'], 'repodata/repomd.xml')), LIBXML_PARSEHUGE | LIBXML_NONET);
        } catch (Exception $e) {
            throw new Exception('Could not parse repomd.xml: ' . $e->getMessage());
        }

        $metadata = [];
        $primary = null;

        foreach ($xml->data as $data) {
            $href = (string) $data->location['href'];
            $checksumType = strtolower((string) $data->checksum['type']);

            if ($href === '') {
                continue;
            }

            if (!isset(self::CHECKSUMS[$checksumType])) {
                throw new Exception('Unsupported checksum type in repomd.xml: ' . htmlspecialchars($checksumType));
            }

            $entry = $this->entry(
                $this->resolvePath($repo['dir'], $href),
                $repo['url'] . '/' . $href,
                isset($data->size) ? (int) $data->size : null,
                (string) $data->checksum,
                self::CHECKSUMS[$checksumType]
            );

            $entry['type'] = (string) $data['type'];
            $metadata[] = $entry;

            if ((string) $data['type'] == 'primary') {
                $primary = $entry;
            }
        }

        return [$metadata, $primary];
    }

    /**
     *  Parse primary metadata file and return the list of packages to retrieve
     */
    private function parsePrimary(array $repo, array $primary): array
    {
        $packages = [];
        $externalBase = false;
        $primaryFile = $this->tempDir . '/primary.xml';

        $this->uncompressToFile($this->workingDir . '/' . $primary['path'], $primaryFile);

        // XMLReader is used for lower memory consumption, primary files can be huge
        $reader = new XMLReader();

        if (!$reader->open($primaryFile, null, LIBXML_PARSEHUGE | LIBXML_NONET)) {
            throw new Exception('Could not open primary metadata file');
        }

        $hasNode = $reader->read();

        while ($hasNode) {
            if ($reader->nodeType != XMLReader::ELEMENT or $reader->localName != 'package') {
                $hasNode = $reader->read();
                continue;
            }

            $package = $reader->expand();

            if (!$package instanceof DOMElement) {
                throw new Exception('Could not parse primary metadata file');
            }

            $location = $package->getElementsByTagName('location')->item(0);
            $checksum = $package->getElementsByTagName('checksum')->item(0);
            $size = $package->getElementsByTagName('size')->item(0);

            // Skip the package subtree, the reader is now on the next node
            $hasNode = $reader->next();

            if (is_null($location) or is_null($checksum)) {
                continue;
            }

            // Packages with a xml:base are downloaded by clients from that base URL, not from the mirror
            if ($location->hasAttribute('xml:base')) {
                $externalBase = true;
            }

            $href = $location->getAttribute('href');
            $checksumType = strtolower($checksum->getAttribute('type'));

            if (!isset(self::CHECKSUMS[$checksumType])) {
                throw new Exception('Unsupported checksum type in primary metadata file: ' . htmlspecialchars($checksumType));
            }

            $packages[] = $this->entry(
                $this->resolvePath($repo['dir'], $href),
                $repo['url'] . '/' . $href,
                is_null($size) ? null : (int) $size->getAttribute('package'),
                trim($checksum->textContent),
                self::CHECKSUMS[$checksumType]
            );
        }

        $reader->close();
        unlink($primaryFile);

        if ($externalBase) {
            $this->taskLogSubStepController->output('Some packages locations have a xml:base attribute: clients will download them from that URL instead of the mirror', 'warning');
        }

        return $packages;
    }

    /**
     *  Resolve a location href relative to a repository directory (href may contain '../')
     *  The resulting path must stay inside the snapshot directory
     */
    private function resolvePath(string $dir, string $href): string
    {
        $segments = [];

        foreach (explode('/', $this->relPath($dir, $href)) as $segment) {
            if ($segment === '' or $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if (empty($segments)) {
                    throw new Exception('Invalid location in upstream metadata (outside of the repository): ' . htmlspecialchars($href));
                }

                array_pop($segments);
                continue;
            }

            $segments[] = $segment;
        }

        return implode('/', $segments);
    }

    private function relPath(string $dir, string $path): string
    {
        return $dir === '' ? $path : $dir . '/' . $path;
    }

    private function entry(string $path, string $url, int|null $size = null, string|null $hash = null, string|null $algo = null): array
    {
        return ['path' => $path, 'url' => $url, 'size' => $size, 'hash' => $hash, 'algo' => $algo];
    }
}
