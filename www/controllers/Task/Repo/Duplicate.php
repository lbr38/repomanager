<?php

namespace Controllers\Task\Repo;

use Exception;

class Duplicate
{
    use \Controllers\Task\Param;
    use Metadata\Create;

    private $sourceRepo;
    private $repo;
    private $rpmRepoController;
    private $debRepoController;
    private $repoSnapshotController;
    private $repoEnvController;
    private $task;
    private $taskLogStepController;
    private $taskLogSubStepController;

    public function __construct(string $taskId)
    {
        $this->sourceRepo = new \Controllers\Repo\Repo();
        $this->repo = new \Controllers\Repo\Repo();
        $this->rpmRepoController = new \Controllers\Repo\Rpm();
        $this->debRepoController = new \Controllers\Repo\Deb();
        $this->task = new \Controllers\Task\Task();
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
         *  Check snap Id parameter
         */
        $requiredParams = array('snap-id');
        $this->taskParamsCheck('Duplicate repository snapshot', $taskParams, $requiredParams);

        /**
         *  Getting all source repo details from its snapshot Id
         *  Do the same for the actual repo to herit all source repo parameters
         */
        $this->sourceRepo->getAllById(null, $taskParams['snap-id'], null);
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Set additionnal params from the actual repo to duplicate
         */
        $taskParams['gpg-sign'] = $this->sourceRepo->getSigned();
        $taskParams['arch'] = $this->sourceRepo->getArch();

        /**
         *  Repo override some parameters defined by the user
         */

        /**
         *  Check and set others task parameters
         */
        $requiredParams = array('name', 'gpg-sign', 'arch');
        $optionalParams = array('group', 'description', 'env');

        $this->taskParamsCheck('Duplicate repository', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, $optionalParams);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('duplicate');

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
     *  Duplicate repo
     */
    public function execute()
    {
        try {
            $this->taskLogStepController->new('duplicating', 'DUPLICATING');
            $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

            /**
             *  Check if source repo snapshot exists
             */
            if ($this->sourceRepo->existsSnapId($this->sourceRepo->getSnapId()) === false) {
                throw new Exception('Source repository snapshot does not exist');
            }

            /**
             *  Check if a repo with the same name already exists
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->rpmRepoController->isActive($this->repo->getName(), $this->repo->getReleasever())) {
                    throw new Exception('A repo <span class="label-black">' . $this->repo->getName() . ' (release ver. ' . $this->repo->getReleasever() . ')</span> already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->debRepoController->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection())) {
                    throw new Exception('A repo <span class="label-black">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> already exists');
                }
            }

            /**
             *  Define source snapshot path
             */
            if ($this->sourceRepo->getPackageType() == 'rpm') {
                $sourceSnapshotPath = REPOS_DIR . '/rpm/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getReleasever() . '/' . $this->sourceRepo->getDate();
            }
            if ($this->sourceRepo->getPackageType() == 'deb') {
                $sourceSnapshotPath = REPOS_DIR . '/deb/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getDist() . '/' . $this->sourceRepo->getSection() . '/' . $this->sourceRepo->getDate();
            }

            /**
             *  Define target snapshot path
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $parentDir = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever();
                $targetSnapshotPath = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/'. $this->repo->getDate();
            }
            if ($this->repo->getPackageType() == 'deb') {
                $parentDir = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection();
                $targetSnapshotPath = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $this->repo->getDate();
            }

            /**
             *  Create parent directory if it does not already exists
             */
            if (!is_dir($parentDir)) {
                if (!mkdir($parentDir, 0770, true)) {
                    throw new Exception('Cannot create directory ' . $parentDir);
                }
            }

            /**
             *  Set temporary dir path
             */
            $tempDir = REPOS_DIR . '/temporary-task-' . $this->task->getId();

            /**
             *  Create the temporary directory if it does not already exist (might exist if the task has been stopped and restarted)
             */
            if (!file_exists($tempDir)) {
                if (!mkdir($tempDir, 0770, true)) {
                    throw new Exception('Cannot create temporary directory ' . $tempDir);
                }
            }

            /**
             *  Get all files and directories in the source repository
             */
            $dirs  = \Controllers\Filesystem\File::recursiveScan($sourceSnapshotPath, 'dir', true);
            $files = \Controllers\Filesystem\File::recursiveScan($sourceSnapshotPath, 'file', true);

            /**
             *  Create all directories in the temporary directory
             */
            foreach ($dirs as $dir) {
                if (!file_exists($tempDir . '/' . $dir)) {
                    if (!mkdir($tempDir . '/' . $dir, 0770, true)) {
                        throw new Exception('Cannot create directory ' . $tempDir . '/' . $dir);
                    }
                }
            }

            $this->taskLogSubStepController->completed();

            /**
             *  Copy all files from the source repository to the temporary directory
             *  Only if the file is not already in the target directory (might happen if the task has been stopped and restarted)
             *  If copy-completed file exists, then it means that the file was already fully copied by a previous task and should not be copied again
             */
            $fileCounter = 0;
            foreach ($files as $file) {
                $fileCounter++;

                /**
                 *  Count total number of files to copy
                 */
                $totalFiles = count($files);

                /**
                 *  Show progress
                 */
                $this->taskLogSubStepController->new('copying-file-' . $fileCounter, 'COPYING FILE (' . $fileCounter . '/' . $totalFiles . ')', 'From ' . $sourceSnapshotPath . '/' . $file . '<br>To ' . $tempDir . '/' . $file);

                /**
                 *  Ignore file if it was already copied (completed file exists)
                 */
                if (file_exists($tempDir . '/' . $file) and file_exists($tempDir . '/' . $file . '.completed')) {
                    $this->taskLogSubStepController->completed('Already exists (ignoring)');
                    continue;
                }

                /**
                 *  If file exists but not the completed file, then it means that the file was not fully copied by a previous task
                 *  Remove the file and copy it again
                 */
                if (file_exists($tempDir . '/' . $file)) {
                    if (!unlink($tempDir . '/' . $file)) {
                        throw new Exception('Cannot remove file ' . $tempDir . '/' . $file);
                    }
                }

                /**
                 *  Copy the file
                 */
                if (!copy($sourceSnapshotPath . '/' . $file, $tempDir . '/' . $file)) {
                    throw new Exception('Cannot copy file ' . $sourceSnapshotPath . '/' . $file . ' to ' . $tempDir . '/' . $file);
                }

                /**
                 *  Create the completed file
                 */
                if (!touch($tempDir . '/' . $file . '.completed')) {
                    throw new Exception('Cannot create copy-completed file ' . $tempDir . '/' . $file . '.completed');
                }

                $this->taskLogSubStepController->completed();
            }

            unset($dirs, $files);

            /**
             *  Rename the temporary directory to the target directory
             */
            $this->taskLogSubStepController->new('moving-temp-dir', 'MOVING TEMPORARY DIRECTORY');
            if (!rename($tempDir, $targetSnapshotPath)) {
                throw new Exception('Cannot rename temporary directory ' . $tempDir . ' to ' . $targetSnapshotPath);
            }
            $this->taskLogSubStepController->completed();

            try {
                /**
                 *  Cleaning completed files now that the temporary directory has been renamed
                 *  Search for all file with '.completed' extension and remove them
                 */
                $files = \Controllers\Filesystem\File::findRecursive($targetSnapshotPath, ['completed'], true);

                foreach ($files as $file) {
                    if (!unlink($file)) {
                        throw new Exception('Cannot remove file ' . $file);
                    }
                }

                /**
                 *  Set step 'DUPLICATING' as completed
                 */
                $this->taskLogStepController->completed();

                /**
                 *  On a deb repo, the duplicated repo metadata must be rebuilded
                 */
                if ($this->repo->getPackageType() == 'deb') {
                    $this->createMetadata();
                }

                $this->taskLogStepController->new('finalizing', 'FINALIZING');

                /**
                 *  Create a symlink to the new repo, only if the user has specified an environment
                 */
                if (!empty($this->repo->getEnv())) {
                    foreach ($this->repo->getEnv() as $env) {
                        $this->taskLogSubStepController->new('pointing-environment', 'POINTING ENVIRONMENT');

                        if ($this->repo->getPackageType() == 'rpm') {
                            $link = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $env;
                        }
                        if ($this->repo->getPackageType() == 'deb') {
                            $link = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $env;
                        }

                        /**
                         *  If a symlink with the same name already exists, we remove it
                         */
                        if (is_link($link)) {
                            if (!unlink($link)) {
                                throw new Exception('Could not remove existing symlink ' . $link);
                            }
                        }

                        /**
                         *  Create symlink
                         */
                        if (!symlink($this->repo->getDate(), $link)) {
                            throw new Exception('Could not point environment to the repository');
                        }

                        $this->taskLogSubStepController->completed();

                        unset($link);
                    }
                }

                $this->taskLogSubStepController->new('inserting-database', 'INSERTING REPOSITORY IN DATABASE');

                /**
                 *  Insert the new repo in database and retrieve its Id
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->rpmRepoController->add($this->repo->getName(), $this->repo->getReleasever(), $this->repo->getSource());
                    $targetRepoId = $this->rpmRepoController->getLastInsertRowID();
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $this->debRepoController->add($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getSource());
                    $targetRepoId = $this->debRepoController->getLastInsertRowID();
                }

                /**
                 *  Add the new repo snapshot in database
                 */
                $this->repoSnapshotController->add($this->repo->getDate(), $this->repo->getTime(), $this->repo->getSigned(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), $this->repo->getType(), $this->repo->getStatus(), $targetRepoId);

                /**
                 *  Retrieve the Id of the new repo snapshot in database
                 */
                $targetSnapId = $this->repoSnapshotController->getLastInsertRowID();

                /**
                 *  Add the new repo environment in database, only if the user has specified an environment
                 */
                if (!empty($this->repo->getEnv())) {
                    foreach ($this->repo->getEnv() as $env) {
                        $this->repoEnvController->add($env, $this->repo->getDescription(), $targetSnapId);
                    }
                }

                /**
                 *  Add the new repo to a group if a group has been specified
                 */
                if (!empty($this->repo->getGroup())) {
                    $this->repo->addRepoIdToGroup($targetRepoId, $this->repo->getGroup());
                }

                /**
                 *  Clean unused repos in groups
                 */
                $this->repo->cleanGroups();

                $this->taskLogSubStepController->completed();
                $this->taskLogStepController->completed();

                /**
                 *  Set task status to done
                 */
                $this->task->setStatus('done');
                $this->task->updateStatus($this->task->getId(), 'done');
            /**
             *  If an error occurred after the temporary directory was renamed, clean the target directory
             */
            } catch (Exception $e) {
                if (file_exists($targetSnapshotPath)) {
                    if (!\Controllers\Filesystem\Directory::deleteRecursive($targetSnapshotPath)) {
                        throw new Exception('An error occurred while finalizing the task, and the target directory ' . $targetSnapshotPath . ' could not be cleaned');
                    }
                }

                /**
                 *  Throw initial exception to set the task as error
                 */
                throw new Exception($e->getMessage());
            }
        } catch (Exception $e) {
            // Set sub step error message and mark step as error
            $this->taskLogSubStepController->error($e->getMessage());
            $this->taskLogStepController->error();

            // Set task status to error
            $this->task->setStatus('error');
            $this->task->updateStatus($this->task->getId(), 'error');
            $this->task->setError('Failed');
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
}
