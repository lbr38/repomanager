<?php

namespace Controllers\Repo\Metadata;

use Controllers\Filesystem\Directory;
use Controllers\Process;
use Exception;

class Deb extends Metadata
{
    private $root;
    private $repo;
    private $dist;
    private $section;
    private $arch;
    private $gpgSign = false;
    private $aptftparchive = '/usr/bin/apt-ftparchive';
    private $customFields = [];

    public function setRoot(string $root): void
    {
        $this->root = $root;
    }

    public function setRepo(string $repo): void
    {
        $this->repo = $repo;
    }

    public function setDist(string $dist): void
    {
        $this->dist = $dist;
    }

    public function setSection(string $section): void
    {
        $this->section = $section;
    }

    public function setArch(array $arch): void
    {
        $this->arch = $arch;
    }

    public function setGpgSign(string $gpgSign): void
    {
        $this->gpgSign = $gpgSign;
    }

    public function setMetadataCustomFields(array $fields): void
    {
        $this->customFields = $fields;
    }

    /**
     *  Create metadata files
     */
    public function create(): void
    {
        // Check if apt-ftparchive is present on the system
        if (!file_exists($this->aptftparchive)) {
            throw new Exception('Could not find apt-ftparchive on the system');
        }

        // Check if root path exists
        if (!is_dir($this->root)) {
            throw new Exception("Repository root directory '" . $this->root . "' does not exist");
        }

        // Target arch must be specified
        if (empty($this->arch)) {
            throw new Exception('Packages architecture(s) must be specified');
        }

        $this->taskLogSubStepController->new('create-metadata', 'GENERATING REPOSITORY METADATA');

        // Define directory to create for the repository
        $dirs = [
            'dists',
            'dists/' . $this->dist,
            'dists/' . $this->dist . '/' . $this->section,
            'pool',
            'cache'
        ];

        // Append binary arch directories to the list of directories to create
        foreach ($this->arch as $arch) {
            if ($arch == 'src') {
                $dirs[] = 'dists/' . $this->dist . '/' . $this->section . '/source';
            } else {
                $dirs[] = 'dists/' . $this->dist . '/' . $this->section . '/binary-' . $arch;
            }
        }

        /**
         *  First, clean all of these directories if they already exist (.e.g if the repository is being rebuilt or it is a duplicate of another one)
         *  Clean all but 'pool' directory, because it might contain packages to add to the repository (e.g. if the repository is being rebuilt or it is a duplicate of another one)
         */
        foreach ($dirs as $dir) {
            // Skip pool directory
            if ($dir == 'pool') {
                continue;
            }

            // Clean directory if it exists
            if (is_dir($this->root . '/' . $dir)) {
                if (!Directory::deleteRecursive($this->root . '/' . $dir)) {
                    throw new Exception('Cannot delete existing directory: ' . $this->root . '/' . $dir);
                }
            }
        }

        // Then create directory structure
        foreach ($dirs as $dir) {
            if (!is_dir($this->root . '/' . $dir)) {
                if (!mkdir($this->root . '/' . $dir, 0770, true)) {
                    throw new Exception("Failed to create directory '" . $this->root . '/' . $dir . "'");
                }
            }
        }

        // Create apt-ftparchive.conf file
        if (!file_put_contents($this->root . '/apt-ftparchive.conf', $this->generateAptFtpArchiveConf())) {
            throw new Exception('Failed to create apt-ftparchive.conf file');
        }

        // Create dist.conf file
        if (!file_put_contents($this->root . '/dist.conf', $this->generateDistConf())) {
            throw new Exception('Failed to create dist.conf file');
        }

        // Create Packages file
        $myprocess = new Process($this->aptftparchive . ' generate ' . escapeshellarg($this->root . '/apt-ftparchive.conf'));
        $myprocess->setBackground(true);
        $myprocess->execute();

        // Retrieve output from process
        $output = $myprocess->getOutput();

        $this->taskLogSubStepController->output($output, 'pre');

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Failed to generate Packages metadata file.');
        }

        $myprocess->close();

        /**
         *  Generate Release file
         *  It is written outside of the scanned directory then moved, otherwise apt-ftparchive would list the
         *  partially written Release file (created by the shell redirection) in its own checksums
         */
        $releaseTmp = $this->root . '/Release.tmp';

        $myprocess = new Process(
            $this->aptftparchive . ' -c ' . escapeshellarg($this->root . '/dist.conf')
            . ' release ' . escapeshellarg($this->root . '/dists/' . $this->dist)
            . ' > ' . escapeshellarg($releaseTmp)
        );
        $myprocess->setBackground(true);
        $myprocess->execute();

        // Retrieve output from process
        $output = $myprocess->getOutput();

        $this->taskLogSubStepController->output($output, 'pre');

        if ($myprocess->getExitCode() != 0) {
            if (file_exists($releaseTmp)) {
                unlink($releaseTmp);
            }

            throw new Exception('Failed to generate Release metadata file');
        }

        $myprocess->close();

        if (!rename($releaseTmp, $this->root . '/dists/' . $this->dist . '/Release')) {
            throw new Exception('Failed to move Release metadata file to ' . $this->root . '/dists/' . $this->dist);
        }

        // Clean configuration files
        if (file_exists($this->root . '/apt-ftparchive.conf')) {
            if (!unlink($this->root . '/apt-ftparchive.conf')) {
                throw new Exception("Failed to remove '" . $this->root . "/apt-ftparchive.conf' file");
            }
        }
        if (file_exists($this->root . '/dist.conf')) {
            if (!unlink($this->root . '/dist.conf')) {
                throw new Exception("Failed to remove '" . $this->root . "/dist.conf' file");
            }
        }

        // Quit here if GPG signature is not enabled
        if ($this->gpgSign != 'true') {
            $this->taskLogSubStepController->completed();
            return;
        }

        // Sign Release file with GPG key
        $distDir = $this->root . '/dists/' . $this->dist;
        $this->sign($distDir . '/Release', $distDir . '/Release.gpg', $distDir . '/InRelease');

        $this->taskLogSubStepController->completed();
    }

    /**
     *  Set the Date field of the Release file to the current date, and sign it again if it is signed
     *
     *  apt ignores a Release file older than the one it already has for the same URL: without this, clients would
     *  keep the packages lists of the previous snapshot when an environment is pointed back to an older snapshot.
     *  The indices referenced by the Release file are left untouched, up to date clients only download it again.
     */
    public function refreshReleaseDate(): void
    {
        $distDir = $this->root . '/dists/' . $this->dist;

        if (!file_exists($distDir . '/Release')) {
            throw new Exception('Could not find Release metadata file in ' . $distDir);
        }

        $content = file_get_contents($distDir . '/Release');

        if ($content === false) {
            throw new Exception('Failed to read Release metadata file');
        }

        // Same format as apt-ftparchive (RFC 1123, UTC)
        $content = preg_replace('/^Date: .*$/m', 'Date: ' . gmdate('D, d M Y H:i:s') . ' UTC', $content, 1, $count);

        if ($content === null or $count != 1) {
            throw new Exception('Could not find Date field in Release metadata file');
        }

        /**
         *  New files are written under a temporary name then moved, so that clients never get a partially written file
         *  The Release file is signed again only if it was signed (InRelease and/or Release.gpg file)
         */
        $files = ['Release' => $this->root . '/Release.tmp'];

        if (file_exists($distDir . '/InRelease') or file_exists($distDir . '/Release.gpg')) {
            $files['Release.gpg'] = $this->root . '/Release.gpg.tmp';
            $files['InRelease'] = $this->root . '/InRelease.tmp';
        }

        try {
            // Remove temporary files that may remain from a previous failure (gpg does not overwrite existing files)
            foreach ($files as $tmpFile) {
                if (file_exists($tmpFile) and !unlink($tmpFile)) {
                    throw new Exception('Failed to remove temporary file ' . $tmpFile);
                }
            }

            if (file_put_contents($files['Release'], $content) === false) {
                throw new Exception('Failed to write Release metadata file');
            }

            if (isset($files['InRelease'])) {
                $this->sign($files['Release'], $files['Release.gpg'], $files['InRelease']);
            }

            // InRelease, used first by apt, is replaced last
            foreach (['Release.gpg', 'Release', 'InRelease'] as $name) {
                if (isset($files[$name]) and !rename($files[$name], $distDir . '/' . $name)) {
                    throw new Exception('Failed to move ' . $name . ' metadata file to ' . $distDir);
                }
            }
        } finally {
            foreach ($files as $tmpFile) {
                if (file_exists($tmpFile)) {
                    unlink($tmpFile);
                }
            }
        }
    }

    /**
     *  Sign a Release file with the GPG signing key: detached signature (Release.gpg) and clearsigned file (InRelease)
     */
    private function sign(string $release, string $releaseGpg, string $inRelease): void
    {
        $myprocess = new Process(
            '/usr/bin/gpg --homedir ' . escapeshellarg(GPGHOME) . ' -u ' . escapeshellarg(GPG_SIGNING_KEYID)
            . ' --output ' . escapeshellarg($releaseGpg)
            . ' -ba ' . escapeshellarg($release)
        );
        $myprocess->execute();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Failed to sign Release metadata file');
        }

        $myprocess->close();

        // Generate InRelease file (clearsigned Release file), preferred by apt over Release + Release.gpg
        $myprocess = new Process(
            '/usr/bin/gpg --homedir ' . escapeshellarg(GPGHOME) . ' -u ' . escapeshellarg(GPG_SIGNING_KEYID)
            . ' --output ' . escapeshellarg($inRelease)
            . ' --clearsign ' . escapeshellarg($release)
        );
        $myprocess->execute();

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Failed to generate InRelease metadata file');
        }

        $myprocess->close();
    }

    /**
     *  Generate apt-ftparchive.conf file
     */
    private function generateAptFtpArchiveConf(): string
    {
        $architectures = '';

        // Generate architectures string
        foreach ($this->arch as $arch) {
            if ($arch == 'src') {
                $architectures .= 'source ';
            } else {
                $architectures .= $arch . ' ';
            }
        }

        $architectures = trim($architectures);

        /**
         *  apt-ftparchive man: https://manpages.ubuntu.com/manpages/noble/en/man1/apt-ftparchive.1.html
         *  e.g:
         *    - https://gist.github.com/aarroyoc/1a96b2f8b01fcf34221a#file-apt-ftparchive-conf
         *    - https://www.linuxquestions.org/questions/blog/bittner-195120/howto-build-your-own-debian-repository-2863/
         *
         */
        $template = <<<EOT
        Dir {
            ArchiveDir "$this->root";
            CacheDir "$this->root/cache";
        };
        Default {
            Packages::Compress ". gzip bzip2";
            Sources::Compress ". gzip bzip2";
            Contents::Compress ". gzip bzip2";
            Translations::Compress ". gzip bzip2";
        };
        TreeDefault {
            Directory "pool/$(SECTION)";
            SrcDirectory "pool/$(SECTION)";
            Packages "$(DIST)/$(SECTION)/binary-$(ARCH)/Packages";
            Sources "$(DIST)/$(SECTION)/source/Sources";
            Contents "$(DIST)/Contents-$(ARCH)";
            BinCacheDB "packages-$(SECTION)-$(ARCH).db";
        };
        Tree "dists/$this->dist" {
            Sections "$this->section";
            Architectures "$architectures";
        }
        EOT;

        return $template;
    }

    /**
     *  Generate dist.conf file
     */
    private function generateDistConf(): string
    {
        $architectures = '';

        // Default values for Release fields
        $origin        = $this->repo . ' > ' . $this->dist . ' > ' . $this->section . ' repository';
        $label         = 'deb packages repository';
        $description   = $this->repo . ' > ' . $this->dist . ' > ' . $this->section . ' repository';

        // Overwrite with custom fields if defined
        if (!empty($this->customFields['origin'])) {
            $origin = $this->customFields['origin'];
        }
        if (!empty($this->customFields['label'])) {
            $label = $this->customFields['label'];
        }
        if (!empty($this->customFields['description'])) {
            $description = $this->customFields['description'];
        }

        // Generate architectures string
        foreach ($this->arch as $arch) {
            if ($arch == 'src') {
                $architectures .= 'source ';
            } else {
                $architectures .= $arch . ' ';
            }
        }

        $architectures = trim($architectures);

        /**
         *  e.g: https://unix.stackexchange.com/a/403489
         *  Required fields: https://unix.stackexchange.com/a/258812
         */
        $template = <<<EOT
        APT::FTPArchive::Release {
            Version        "1.0";
            Origin         "$origin";
            Label          "$label";
            Suite          "$this->dist";
            Codename       "$this->dist";
            Architectures  "$architectures";
            Components     "$this->section";
            Description    "$description";
        }
        EOT;

        return $template;
    }
}
