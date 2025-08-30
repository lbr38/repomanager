<?php

namespace Controllers\Task\Repo;

use Exception;

class Update
{
    use \Controllers\Task\Param;
    use Package\Sync;
    use Package\Sign;
    use Metadata\Create;
    use Finalize;

    private $sourceRepo;
    private $repo;
    private $task;
    private $rpmRepoController;
    private $debRepoController;
    private $repoSnapshotController;
    private $repoEnvController;
    private $taskLogStepController;
    private $taskLogSubStepController;
    private $packagesToSign = null;

    public function __construct(string $taskId)
    {
        $this->sourceRepo = new \Controllers\Repo\Repo();
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->rpmRepoController = new \Controllers\Repo\Rpm();
        $this->debRepoController = new \Controllers\Repo\Deb();
        $this->repoSnapshotController = new \Controllers\Repo\Snapshot();
        $this->repoEnvController = new \Controllers\Repo\Environment();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);

        /**
         *  Retrieve task params
         */
        $task = $this->task->getById($taskId);
        $taskParams = json_decode($task['Raw_params'], true);

        /**
         *  If task is a scheduled task and it is recurring, then update the snap-id parameter to be the last snap-id
         *  If not, the task could try to update a repo with an old and possibly deleted snap-id
         */
        if ($taskParams['schedule']['scheduled'] == 'true') {
            if ($taskParams['schedule']['schedule-type'] == 'recurring') {
                /**
                 *  Retrieve repository latest snapshot Id, from the repo Id
                 */
                $latestSnapId = $this->repo->getLatestSnapId($taskParams['repo-id']);

                /**
                 *  Throw error if no snapshot is found
                 */
                if (empty($latestSnapId)) {
                    throw new Exception('Could not find the latest snapshot Id for this repository');
                }

                /**
                 *  Update snap-id parameter
                 */
                $taskParams['snap-id'] = $latestSnapId;

                /**
                 *  Update raw_params in the database
                 */
                $this->task->updateRawParams($taskId, json_encode($taskParams));
            }
        }

        /**
         *  Check source repo snap Id parameter
         */
        $requiredParams = array('snap-id');
        $this->taskParamsCheck('Update repository', $taskParams, $requiredParams);

        /**
         *  Getting all source repo details from its snapshot Id
         *  Do the same for the actual repo to herit all source repo parameters
         */
        $this->sourceRepo->getAllById(null, $taskParams['snap-id'], null);
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Override with parameters defined by the user
         */
        // Case it's a mirror repo
        if ($this->repo->getType() == 'mirror') {
            $requiredParams = array('gpg-check', 'gpg-sign', 'arch');
            $optionalParams = array('env', 'package-include', 'package-exclude');
        }
        // Case it's a local repo
        if ($this->repo->getType() == 'local') {
            $requiredParams = array('gpg-sign', 'arch');
            $optionalParams = array('env');
        }

        $this->taskParamsCheck('Update repo', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, $optionalParams);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('update');

        /**
         *  Start task
         */
        $this->task->setDate(date('Y-m-d'));
        $this->task->setTime(date('H:i:s'));
        $this->task->updateDate($taskId, $this->task->getDate());
        $this->task->updateTime($taskId, $this->task->getTime());
        $this->task->start();
    }

    /**
     *  Update repository
     */
    public function execute()
    {
        /**
         *  Define default date and time
         */
        $this->repo->setDate(date('Y-m-d'));
        $this->repo->setTime(date('H:i'));

        try {
            /**
             *  Sync packages (if mirror repo)
             */
            if ($this->repo->getType() == 'mirror') {
                $this->syncPackage();
            }

            /**
             *  Update repository (if local repo)
             */
            if ($this->repo->getType() == 'local') {
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

            /**
             *  Set task status to done
             */
            $this->task->setStatus('done');
            $this->task->updateStatus($this->task->getId(), 'done');
        } catch (Exception $e) {
            // Set sub step error message and mark step as error
            $this->taskLogSubStepController->error($e->getMessage());
            $this->taskLogStepController->error();

            // Set task status to error
            $this->task->setStatus('error');
            $this->task->updateStatus($this->task->getId(), 'error');
            $this->task->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = \Controllers\Common::convertMicrotime($this->task->getDuration());

        /**
         *  End task
         */
        $this->taskLogStepController->new('duration', 'DURATION');
        $this->taskLogStepController->none('Total duration: ' . $duration);
        $this->task->end();
    }

    /**
     *  Update local repository
     */
    private function updateLocal()
    {
        $this->taskLogStepController->new('updating', 'UPDATING');

        try {
            $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

            /**
             *  Check if a snapshot exists in the database
             */
            if ($this->repo->existsSnapId($this->repo->getSnapId()) === false) {
                throw new Exception('Specified repo snapshot does not exist');
            }

            /**
             *  We cannot update a snapshot in the same day
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->rpmRepoController->existsSnapDate($this->repo->getName(), $this->repo->getReleasever(), $this->repo->getDate())) {
                    throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->debRepoController->existsSnapDate($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getDate())) {
                    throw new Exception('A snapshot already exists on the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
                }
            }

            /**
             *  Arch must be specified
             */
            if (empty($this->repo->getArch())) {
                throw new Exception('Packages arch must be specified');
            }

            /**
             *  Define snapshot directory path
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $snapshotPath = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . DATE_YMD;
            }
            if ($this->repo->getPackageType() == 'deb') {
                $snapshotPath = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . DATE_YMD;
            }

            /**
             *  Define previous snapshot directory path
             */
            if ($this->sourceRepo->getPackageType() == 'rpm') {
                $previousSnapshotDir = REPOS_DIR . '/rpm/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getReleasever() . '/' . $this->sourceRepo->getDate();
            }
            if ($this->sourceRepo->getPackageType() == 'deb') {
                $previousSnapshotDir = REPOS_DIR . '/deb/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getDist() . '/' . $this->sourceRepo->getSection() . '/' . $this->sourceRepo->getDate();
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
                if (!\Controllers\Filesystem\Directory::deleteRecursive($snapshotPath)) {
                    throw new Exception('Cannot delete existing directory: ' . $snapshotPath);
                }
            }

            $this->taskLogSubStepController->completed();
            $this->taskLogSubStepController->new('search-packages', 'SEARCHING PACKAGES IN PREVIOUS SNAPSHOT');

            /**
             *  Search for packages in the previous snapshot directory
             */
            try {
                if ($this->repo->getPackageType() == 'deb') {
                    $packages = \Controllers\Filesystem\File::findRecursive($previousSnapshotDir . '/pool/' . $this->sourceRepo->getSection(), ['deb', 'dsc', 'gz', 'xz']);
                }

                if ($this->repo->getPackageType() == 'rpm') {
                    $packages = \Controllers\Filesystem\File::findRecursive($previousSnapshotDir . '/packages', ['rpm']);
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
            if ($this->repo->getPackageType() == 'deb') {
                // Create pool directory
                if (!mkdir($snapshotPath . '/pool/' . $this->repo->getSection(), 0770, true)) {
                    throw new Exception('Cannot create directory: ' . $snapshotPath . '/pool/' . $this->repo->getSection());
                }
            }
            if ($this->repo->getPackageType() == 'rpm') {
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

                /**
                 *  Deduplication
                 *  Create hardlink to the previous snapshot package
                 */
                if (REPO_DEDUPLICATION) {
                    $this->taskLogSubStepController->new('hardlink-package-' . $packageCounter, 'LINKING PACKAGE TO PREVIOUS SNAPSHOT (' . $packageCounter . '/' . $totalPackages . ')', $packagePath);

                    if ($this->repo->getPackageType() == 'deb') {
                        if (!link($packagePath, $snapshotPath . '/pool/' . $this->repo->getSection() . '/' . $name)) {
                            throw new Exception('Cannot create hard link to package: ' . $packagePath);
                        }
                    }
                    if ($this->repo->getPackageType() == 'rpm') {
                        if (!link($packagePath, $snapshotPath . '/packages/' . $name)) {
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

                    if ($this->repo->getPackageType() == 'deb') {
                        if (!copy($packagePath, $snapshotPath . '/pool/' . $this->repo->getSection() . '/' . $name)) {
                            throw new Exception('Cannot copy package: ' . $packagePath);
                        }
                    }
                    if ($this->repo->getPackageType() == 'rpm') {
                        if (!copy($packagePath, $snapshotPath . '/packages/' . $name)) {
                            throw new Exception('Cannot copy package: ' . $packagePath);
                        }
                    }
                }

                $this->taskLogSubStepController->completed();
            }

            $this->taskLogStepController->completed();
        } catch (Exception $e) {
            /**
             *  Throw exception with mirror error message
             */
            throw new Exception($e->getMessage());
        }
    }
}
