<?php

namespace Controllers\Mirror\Sync;

use Controllers\Utils\Apk;
use Controllers\Utils\Validate;
use Exception;

/**
 *  Mirroring of an Alpine Linux (apk) repository
 *
 *  The source URL is the repository URL as declared in /etc/apk/repositories, in which $branch is replaced by the branch
 *  (e.g. v3.24, edge) and $repo by each repository (e.g. main, community): https://dl-cdn.alpinelinux.org/alpine/$branch/$repo.
 *  As apk does, the architecture is appended to it: <url>/<arch>/APKINDEX.tar.gz is the signed index of the repository, listing
 *  each package with its size and the checksum of its control part. Packages are <url>/<package arch>/<name>-<version>.apk.
 *
 *  A mirror contains one branch (its release version), each repository of each arch is mirrored in <snapshot>/<repo>/<arch>/,
 *  whatever the source URL layout, so that clients always use: <mirror URL>/<repo>
 *  The branch and repositories are optional if the URL does not contain $branch and $repo (third-party repository with a single
 *  branch or repository, e.g. https://apk.corretto.aws): without repositories, the repository is mirrored in <snapshot>/<arch>/
 *  and clients use <mirror URL>
 */
class Alpine extends Sync
{
    // Public keys of the source repository (key name => PEM content), used to verify the indexes signature
    private array $keys = [];

    // Mirrored repositories: name, arch, URL and index checksum (to detect upstream changes)
    private array $repos = [];

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
     *  Retrieve the indexes and packages of each selected repository and architecture
     */
    protected function syncFiles(): void
    {
        if (empty($this->params['branch']) and str_contains($this->url, '$branch')) {
            throw new Exception('Branch must be specified');
        }

        if (empty($this->params['repos']) and str_contains($this->url, '$repo')) {
            throw new Exception('At least one repository must be specified');
        }

        if (empty($this->params['arch'])) {
            throw new Exception('At least one architecture must be specified');
        }

        $this->repos = [];
        $packages = [];

        // Without repositories, the source URL is a single repository, mirrored at the root of the snapshot
        $repoNames = !empty($this->params['repos']) ? $this->params['repos'] : [''];

        foreach ($repoNames as $repoName) {
            foreach ($this->params['arch'] as $arch) {
                $repo = [
                    'name'  => $repoName,
                    'arch'  => $arch,
                    'url'   => str_replace(['$branch', '$repo'], [$this->params['branch'] ?? '', $repoName], $this->url),
                    // Directory of the repository in the snapshot, the architecture directories are inside
                    'dir'   => $repoName === '' ? '' : $repoName . '/',
                    'label' => ($repoName === '' ? '' : $repoName . '/') . $arch
                ];

                $repo['index'] = $repo['dir'] . $arch . '/APKINDEX.tar.gz';
                $label = ' (' . $repo['label'] . ')';

                $this->taskLogSubStepController->new('retrieve-metadata-' . $repoName . '-' . $arch, 'RETRIEVING INDEX' . $label, 'From ' . $repo['url'] . '/' . $arch);

                $index = $this->retrieveIndex($repo);

                $repo['indexHash'] = hash_file('sha256', $this->workingDir . '/' . $repo['index']);
                $this->repos[] = $repo;

                $this->taskLogSubStepController->new('parse-index-' . $repoName . '-' . $arch, 'PARSING PACKAGES LIST' . $label);

                $repoPackages = $this->parseIndex($repo, $index['apkindex']);
                array_push($packages, ...$repoPackages);

                $this->taskLogSubStepController->completed(count($repoPackages) . ' package(s) found');
            }
        }

        if (empty($packages)) {
            throw new Exception('No package found in the source repository');
        }

        $this->fetchPackages($packages);
    }

    /**
     *  Return true if an index has changed upstream for any of the mirrored repositories
     */
    protected function upstreamChanged(): bool
    {
        foreach ($this->repos as $repo) {
            $tempFile = $this->tempDir . '/index.check';

            if (!$this->downloader->downloadFile($repo['url'] . '/' . $repo['arch'] . '/APKINDEX.tar.gz', $tempFile)) {
                return true;
            }

            $changed = hash_file('sha256', $tempFile) !== $repo['indexHash'];

            unlink($tempFile);

            if ($changed) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Retrieve the index of a repository and verify its signature, return its content (see Apk::readIndex())
     */
    private function retrieveIndex(array $repo): array
    {
        // The index has no known checksum, it is always downloaded
        $present = $this->fetch([$this->entry($repo['index'], $repo['url'] . '/' . $repo['arch'] . '/APKINDEX.tar.gz')], true, true);

        if (!in_array($repo['index'], $present)) {
            throw new Exception('No <code>APKINDEX.tar.gz</code> index found for ' . htmlspecialchars($repo['label']) . '. Please check the source repository URL, branch, repository and architecture.');
        }

        try {
            $index = Apk::readIndex($this->workingDir . '/' . $repo['index']);
        } catch (Exception $e) {
            throw new Exception('Could not read the index of ' . htmlspecialchars($repo['label']) . ': ' . $e->getMessage());
        }

        // The description is usually the version of the index (e.g. v3.24.4-164-gc66a391d1b1)
        if (!empty($index['description'])) {
            $this->taskLogSubStepController->output('Index version: ' . htmlspecialchars($index['description']));
        }

        if ($this->checkSignature) {
            if (empty($index['signatures'])) {
                throw new Exception('The index of ' . htmlspecialchars($repo['label']) . ' is not signed by the source repository. Disable the signature check to mirror an unsigned repository.');
            }

            $signer = Apk::verifySignatures($index['signatures'], $index['signed'], $this->keys);

            $this->taskLogSubStepController->output('Index signature verified: ' . htmlspecialchars($signer));
        }

        $this->taskLogSubStepController->completed();

        return $index;
    }

    /**
     *  Parse the APKINDEX file of a repository, return the list of packages to retrieve
     *  As apk clients do, a package is retrieved from the directory of its own architecture (official indexes rewrite it to the index architecture)
     */
    private function parseIndex(array $repo, string $apkindex): array
    {
        $packages = [];

        foreach (Apk::parseIndex($apkindex) as $package) {
            if (empty($package['P']) or !isset($package['V']) or $package['V'] === '') {
                continue;
            }

            $arch = !empty($package['A']) ? $package['A'] : $repo['arch'];
            $filename = $package['P'] . '-' . $package['V'] . '.apk';

            if (!Validate::alphaNumericHyphen($arch)) {
                throw new Exception('Invalid package architecture in the index of ' . htmlspecialchars($repo['label']) . ': ' . htmlspecialchars($arch));
            }

            if (str_contains($filename, '/') or !Validate::alphaNumericHyphen($filename, ['.', '+', '@', '~', ':'])) {
                throw new Exception('Invalid package file name in the index of ' . htmlspecialchars($repo['label']) . ': ' . htmlspecialchars($filename));
            }

            // The checksum of the control part of the package ('Q1' + base64 SHA1), see Controllers\Utils\Apk
            $hash = null;
            $algo = null;

            if (!empty($package['C']) and str_starts_with($package['C'], 'Q1')) {
                $hash = $package['C'];
                $algo = Apk::IDENTITY_ALGO;
            }

            $size = (isset($package['S']) and ctype_digit($package['S'])) ? (int) $package['S'] : null;

            $packages[] = $this->entry($repo['dir'] . $arch . '/' . $filename, $repo['url'] . '/' . $arch . '/' . $filename, $size, $hash, $algo);
        }

        return $packages;
    }
}
