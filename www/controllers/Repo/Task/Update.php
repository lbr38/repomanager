<?php

namespace Controllers\Repo\Task;

use \Controllers\Filesystem\Directory;
use \Controllers\Filesystem\File;
use Exception;

class Update extends \Controllers\Task\Execution
{
    use \Controllers\Repo\Package\Sync;
    use \Controllers\Repo\Package\Sign;
    use \Controllers\Repo\Metadata\Create;
    use Finalize;

    private $packagesToSign = null;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'update');

        // Get source repository details from its snapshot Id
        $this->sourceRepoController->getAllById(null, $this->params['snap-id'], null);

        // Execute the task
        try {
            $this->execute();
        } catch (Exception $e) {
            $this->status = 'error';
            $this->error = $e->getMessage();
        }

        // End the task
        $this->end();
    }

    /**
     *  Update repository
     */
    public function execute()
    {
        /**
         *  Define default date and time
         */
        $this->repoController->setDate(date('Y-m-d'));
        $this->repoController->setTime(date('H:i'));

        /**
         *  Sync packages (if mirror repo)
         */
        if ($this->repoController->getType() == 'mirror') {
            $this->syncPackage();
        }

        /**
         *  Update repository (if local repo)
         */
        if ($this->repoController->getType() == 'local') {
            $this->updateLocal();
        }

        /**
         *  Sign repo / packages
         */
        $this->signPackage();

        /**
         *  Create repo and symlinks
         */
        $this->createMetadata();

        /**
         *  Finalize repo (add to database and apply rights)
         */
        $this->finalize();
    }

    /**
     *  Update local repository
     */
    private function updateLocal()
    {
        $this->taskLogStepController->new('updating', 'UPDATING');
        $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

        /**
         *  Check if a snapshot exists in the database
         */
        if ($this->repoController->existsSnapId($this->repoController->getSnapId()) === false) {
            throw new Exception('Specified repo snapshot does not exist');
        }

        /**
         *  We cannot update a snapshot in the same day
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            if ($this->rpmRepoController->existsSnapDate($this->repoController->getName(), $this->repoController->getReleasever(), $this->repoController->getDate())) {
                throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repoController->getDateFormatted() . '</span>');
            }
        }
        if ($this->repoController->getPackageType() == 'deb') {
            if ($this->debRepoController->existsSnapDate($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $this->repoController->getDate())) {
                throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repoController->getDateFormatted() . '</span>');
            }
        }

        /**
         *  Arch must be specified
         */
        if (empty($this->repoController->getArch())) {
            throw new Exception('Packages arch must be specified');
        }

        /**
         *  Define snapshot directory path
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . DATE_YMD;
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . DATE_YMD;
        }

        /**
         *  Define previous snapshot directory path
         */
        if ($this->sourceRepoController->getPackageType() == 'rpm') {
            $previousSnapshotDir = REPOS_DIR . '/rpm/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getReleasever() . '/' . $this->sourceRepoController->getDate();
        }
        if ($this->sourceRepoController->getPackageType() == 'deb') {
            $previousSnapshotDir = REPOS_DIR . '/deb/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getDist() . '/' . $this->sourceRepoController->getSection() . '/' . $this->sourceRepoController->getDate();
        }

        /**
         *  Check that previous snapshot directory exists
         */
        if (!is_dir($previousSnapshotDir)) {
            throw new Exception('Previous snapshot directory does not exist: ' . $previousSnapshotDir);
        }

        /**
         *  If target directory already exists, delete it
         */
        if (is_dir($snapshotPath)) {
            if (!\Directory::deleteRecursive($snapshotPath)) {
                throw new Exception('Cannot delete existing directory: ' . $snapshotPath);
            }
        }

        $this->taskLogSubStepController->completed();
        $this->taskLogSubStepController->new('search-packages', 'SEARCHING PACKAGES IN PREVIOUS SNAPSHOT');

        /**
         *  Search for packages in the previous snapshot directory
         */
        try {
            if ($this->repoController->getPackageType() == 'deb') {
                $packages = File::findRecursive($previousSnapshotDir . '/pool/' . $this->sourceRepoController->getSection(), ['deb', 'dsc', 'gz', 'xz']);
            }

            if ($this->repoController->getPackageType() == 'rpm') {
                $packages = File::findRecursive($previousSnapshotDir . '/packages', ['rpm']);
            }
        } catch (Exception $e) {
            throw new Exception('Error while retrieving previous snapshot packages: ' . $e->getMessage());
        }

        /**
         *  Count number of packages found
         */
        $totalPackages = count($packages);
        $packageCounter = 0;

        $this->taskLogSubStepController->completed($totalPackages . ' package(s) found');

        /**
         *  Create target pool/packages directory
         */
        if ($this->repoController->getPackageType() == 'deb') {
            // Create pool directory
            if (!mkdir($snapshotPath . '/pool/' . $this->repoController->getSection(), 0770, true)) {
                throw new Exception('Cannot create directory: ' . $snapshotPath . '/pool/' . $this->repoController->getSection());
            }
        }
        if ($this->repoController->getPackageType() == 'rpm') {
            // Create packages directory. As it is a local repository, we don't need to create arch subdirectories as all packages are in the same directory
            if (!mkdir($snapshotPath . '/packages', 0770, true)) {
                throw new Exception('Cannot create directory: ' . $snapshotPath . '/packages');
            }
        }

        /**
         *  Deduplication/Copy packages from previous snapshot to the new snapshot
         */
        foreach ($packages as $packagePath) {
            // Get package name
            $name = basename($packagePath);

            // Increment counter
            $packageCounter++;

            // Define parent dir and target path
            if ($this->repoController->getPackageType() == 'rpm') {
                $targetPath = $snapshotPath . '/packages/' . $name;

                foreach (RPM_ARCHS as $arch) {
                    if (preg_match("#\.$arch\.#", $name)) {
                        $parentDir  = $snapshotPath . '/packages/' . $arch;
                        break;
                    }
                }

                // If the package is a source package then move it to the SRPMS subfolder
                if (preg_match("#\.src\.#", $name)) {
                    $parentDir  = $snapshotPath . '/packages/SRPMS';
                }

                // If no architecture has been found then we set it to 'noarch'
                if (empty($parentDir)) {
                    $parentDir = $snapshotPath . '/packages/noarch';
                }

                $targetPath = $parentDir . '/' . $name;
            }

            if ($this->repoController->getPackageType() == 'deb') {
                $parentDir  = $snapshotPath . '/pool/' . $this->repoController->getSection();
                $targetPath = $snapshotPath . '/pool/' . $this->repoController->getSection() . '/' . $name;
            }

            // Create parent dir if not exists
            if (!is_dir($parentDir)) {
                if (!mkdir($parentDir, 0770, true)) {
                    throw new Exception('Cannot create directory: ' . $parentDir);
                }
            }

            /**
             *  Deduplication
             *  Create hardlink to the previous snapshot package
             */
            if (REPO_DEDUPLICATION) {
                $this->taskLogSubStepController->new('hardlink-package-' . $packageCounter, 'LINKING PACKAGE TO PREVIOUS SNAPSHOT (' . $packageCounter . '/' . $totalPackages . ')', $packagePath);

                if ($this->repoController->getPackageType() == 'deb') {
                    if (!link($packagePath, $targetPath)) {
                        throw new Exception('Cannot create hard link to package: ' . $packagePath);
                    }
                }
                if ($this->repoController->getPackageType() == 'rpm') {
                    if (!link($packagePath, $targetPath)) {
                        throw new Exception('Cannot create hard link to package: ' . $packagePath);
                    }
                }
            }

            /**
             *  When deduplication is disabled
             *  Copy the package from the previous snapshot to the new snapshot
             */
            if (!REPO_DEDUPLICATION) {
                $this->taskLogSubStepController->new('copy-package-' . $packageCounter, 'COPYING PACKAGE TO NEW SNAPSHOT (' . $packageCounter . '/' . $totalPackages . ')', $packagePath);

                if ($this->repoController->getPackageType() == 'deb') {
                    if (!copy($packagePath, $targetPath)) {
                        throw new Exception('Cannot copy package: ' . $packagePath . ' to ' . $targetPath);
                    }
                }
                if ($this->repoController->getPackageType() == 'rpm') {
                    if (!copy($packagePath, $targetPath)) {
                        throw new Exception('Cannot copy package: ' . $packagePath . ' to ' . $targetPath);
                    }
                }
            }

            $this->taskLogSubStepController->completed();
        }

        $this->taskLogStepController->completed();
    }
}
