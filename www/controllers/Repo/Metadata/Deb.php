<?php

namespace Controllers\Repo\Metadata;

use \Controllers\Filesystem\Directory;
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
    private $task;

    public function setRoot(string $root)
    {
        $this->root = $root;
    }

    public function setRepo(string $repo)
    {
        $this->repo = $repo;
    }

    public function setDist(string $dist)
    {
        $this->dist = $dist;
    }

    public function setSection(string $section)
    {
        $this->section = $section;
    }

    public function setArch(array $arch)
    {
        $this->arch = $arch;
    }

    public function setGpgSign(string $gpgSign)
    {
        $this->gpgSign = $gpgSign;
    }

    /**
     *  Create metadata files
     */
    public function create()
    {
        /**
         *  Check which of apt-ftparchive is present on the system
         */
        if (!file_exists($this->aptftparchive)) {
            throw new Exception('Could not find apt-ftparchive on the system');
        }

        /**
         *  Check if root path exists
         */
        if (!is_dir($this->root)) {
            throw new Exception("Repository root directory '" . $this->root . "' does not exist");
        }

        /**
         *  Target arch must be specified
         */
        if (empty($this->arch)) {
            throw new Exception('Packages architecture(s) must be specified');
        }

        $this->taskLogSubStepController->new('create-metadata', 'GENERATING REPOSITORY METADATA');

        /**
         *  Define directory to create for the repository
         */
        $dirs = [
            'dists',
            'dists/' . $this->dist,
            'dists/' . $this->dist . '/' . $this->section,
            'pool',
            'cache'
        ];

        /**
         *  Append binary arch directories to the list of directories to create
         */
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
            /**
             *  Skip pool directory
             */
            if ($dir == 'pool') {
                continue;
            }

            /**
             *  Clean directory if it exists
             */
            if (is_dir($this->root . '/' . $dir)) {
                if (!Directory::deleteRecursive($this->root . '/' . $dir)) {
                    throw new Exception('Cannot delete existing directory: ' . $this->root . '/' . $dir);
                }
            }
        }

        /**
         *  Then create directory structure
         */
        foreach ($dirs as $dir) {
            if (!is_dir($this->root . '/' . $dir)) {
                if (!mkdir($this->root . '/' . $dir, 0770, true)) {
                    throw new Exception("Failed to create directory '" . $this->root . '/' . $dir . "'");
                }
            }
        }

        /**
         *  Create apt-ftparchive.conf file
         */
        if (!file_put_contents($this->root . '/apt-ftparchive.conf', $this->generateAptFtpArchiveConf())) {
            throw new Exception('Failed to create apt-ftparchive.conf file');
        }

        /**
         *  Create dist.conf file
         */
        if (!file_put_contents($this->root . '/dist.conf', $this->generateDistConf())) {
            throw new Exception('Failed to create dist.conf file');
        }

        /**
         *  Create Packages file
         */
        $myprocess = new \Controllers\Process($this->aptftparchive . ' generate ' . $this->root . '/apt-ftparchive.conf');
        $myprocess->setBackground(true);
        $myprocess->execute();

        /**
         *  Retrieve PID of the launched process
         *  Then write PID to main PID file
         */
        $this->taskController->addsubpid($myprocess->getPid());

        /**
         *  Retrieve output from process
         */
        $output = $myprocess->getOutput();

        $this->taskLogSubStepController->output($output, 'pre');

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Failed to generate Packages metadata file.');
        }

        $myprocess->close();

        /**
         *  Generate Release file
         */
        $myprocess = new \Controllers\Process($this->aptftparchive . ' -c ' . $this->root . '/dist.conf release ' . $this->root . '/dists/' . $this->dist . ' > ' . $this->root . '/dists/' . $this->dist . '/Release');
        $myprocess->setBackground(true);
        $myprocess->execute();

        /**
         *  Retrieve PID of the launched process
         *  Then write PID to main PID file
         */
        $this->taskController->addsubpid($myprocess->getPid());

        /**
         *  Retrieve output from process
         */
        $output = $myprocess->getOutput();

        $this->taskLogSubStepController->output($output, 'pre');

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Failed to generate Release metadata file');
        }

        $myprocess->close();

        /**
         *  Clean configuration files
         */
        if (file_exists($this->root . '/apt-ftparchive.conf')) {
            if (!unlink($this->root . '/apt-ftparchive.conf')) {
                throw new Exception("Failed to clean '" . $this->root . "/apt-ftparchive.conf' file");
            }
        }
        if (file_exists($this->root . '/dist.conf')) {
            if (!unlink($this->root . '/dist.conf')) {
                throw new Exception("Failed to clean '" . $this->root . "/dist.conf' file");
            }
        }

        /**
         *  Quit here if GPG signature is not enabled
         */
        if ($this->gpgSign != 'true') {
            $this->taskLogSubStepController->completed();
            return;
        }

        /**
         *  Sign Release file with GPG key
         */
        $myprocess = new \Controllers\Process('/usr/bin/gpg --homedir ' . GPGHOME . ' -u ' . GPG_SIGNING_KEYID . ' --output ' . $this->root . '/dists/' . $this->dist . '/Release.gpg -ba ' . $this->root . '/dists/' . $this->dist . '/Release');
        $myprocess->execute();

        /**
         *  Retrieve PID of the launched process
         *  Then write PID to main PID file
         */
        $this->taskController->addsubpid($myprocess->getPid());

        if ($myprocess->getExitCode() != 0) {
            throw new Exception('Failed to sign Release metadata file');
        }

        $myprocess->close();

        $this->taskLogSubStepController->completed();
    }

    /**
     *  Generate apt-ftparchive.conf file
     */
    private function generateAptFtpArchiveConf()
    {
        $architectures = '';

        /**
         *  Generate architectures string
         */
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
    private function generateDistConf()
    {
        $architectures = '';

        /**
         *  Generate architectures string
         */
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
            Origin         "$this->repo > $this->dist > $this->section repository";
            Label          "deb packages repository";
            Suite          "$this->dist";
            Codename       "$this->dist";
            Architectures  "$architectures";
            Components     "$this->section";
            Description    "$this->repo > $this->dist > $this->section repository";
        }
        EOT;

        return $template;
    }
}
