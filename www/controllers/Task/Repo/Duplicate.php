<?php

namespace Controllers\Task\Repo;

use Exception;

class Duplicate
{
    use \Controllers\Task\Param;
    use Metadata\Create;

    private $sourceRepo;
    private $repo;
    private $repoEnvController;
    private $task;
    private $taskLogStepController;
    private $taskLogSubStepController;

    public function __construct(string $taskId)
    {
        $this->sourceRepo = new \Controllers\Repo\Repo();
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
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
                if ($this->repo->isActive($this->repo->getName()) === true) {
                    throw new Exception('A repo <span class="label-black">' . $this->repo->getName() . '</span> already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->repo->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()) === true) {
                    throw new Exception('A repo <span class="label-black">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> already exists');
                }
            }

            /**
             *  Set target dir path
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $targetDir = REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName();
            }
            if ($this->repo->getPackageType() == 'deb') {
                $targetDir = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection();

                /**
                 *  Prepare the target directory by creating the dist directory if it does not already exist
                 */
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist(), 0770, true)) {
                        throw new Exception('Cannot create directory ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist());
                    }
                }
            }

            /**
             *  Set source repository path
             */
            if ($this->sourceRepo->getPackageType() == 'rpm') {
                $sourceDir = REPOS_DIR . '/' . $this->sourceRepo->getDateFormatted() . '_' . $this->sourceRepo->getName();
            }
            if ($this->sourceRepo->getPackageType() == 'deb') {
                $sourceDir = REPOS_DIR . '/' . $this->sourceRepo->getName() . '/' . $this->sourceRepo->getDist() . '/' . $this->sourceRepo->getDateFormatted() . '_' . $this->sourceRepo->getSection();
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
            $dirs  = \Controllers\Filesystem\File::recursiveScan($sourceDir, 'dir', true);
            $files = \Controllers\Filesystem\File::recursiveScan($sourceDir, 'file', true);

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
                $this->taskLogSubStepController->new('copying-file-' . $fileCounter, 'COPYING FILE (' . $fileCounter . '/' . $totalFiles . ')', 'From ' . $sourceDir . '/' . $file . '<br>To ' . $tempDir . '/' . $file);

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
                if (!copy($sourceDir . '/' . $file, $tempDir . '/' . $file)) {
                    throw new Exception('Cannot copy file ' . $sourceDir . '/' . $file . ' to ' . $tempDir . '/' . $file);
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
            if (!rename($tempDir, $targetDir)) {
                throw new Exception('Cannot rename temporary directory ' . $tempDir . ' to ' . $targetDir);
            }

            try {
                /**
                 *  Cleaning completed files now that the temporary directory has been renamed
                 *  Search for all file with '.completed' extension and remove them
                 */
                $files = \Controllers\Filesystem\File::findRecursive($targetDir, ['completed'], true);

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
                            $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getName();
                            $link = REPOS_DIR . '/' . $this->repo->getName() . '_' . $env;
                        }
                        if ($this->repo->getPackageType() == 'deb') {
                            $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getSection();
                            $link = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $env;
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
                        if (!symlink($targetFile, $link)) {
                            throw new Exception('Could not point environment to the repository');
                        }

                        $this->taskLogSubStepController->completed();

                        unset($targetFile, $link);
                    }
                }

                $this->taskLogSubStepController->new('inserting-database', 'INSERTING REPOSITORY IN DATABASE');

                /**
                 *  Insert the new repo in database
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->add($this->repo->getSource(), 'rpm', $this->repo->getName());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->add($this->repo->getSource(), 'deb', $this->repo->getName());
                }

                /**
                 *  Retrieve the Id of the new repo in database
                 */
                $targetRepoId = $this->repo->getLastInsertRowID();

                if ($this->repo->getPackageType() == 'rpm') {
                    /**
                     *  Set repo releasever
                     */
                    $this->repo->updateReleasever($targetRepoId, $this->repo->getReleasever());
                }

                if ($this->repo->getPackageType() == 'deb') {
                    /**
                     *  Set repo dist and section
                     */
                    $this->repo->updateDist($targetRepoId, $this->repo->getDist());
                    $this->repo->updateSection($targetRepoId, $this->repo->getSection());
                }

                /**
                 *  Add the new repo snapshot in database
                 */
                $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), $this->repo->getSigned(), $this->repo->getArch(), array(), $this->repo->getPackagesToInclude(), $this->repo->getPackagesToExclude(), $this->repo->getType(), $this->repo->getStatus(), $targetRepoId);

                /**
                 *  Retrieve the Id of the new repo snapshot in database
                 */
                $targetSnapId = $this->repo->getLastInsertRowID();

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
                if (file_exists($targetDir)) {
                    if (!\Controllers\Filesystem\Directory::deleteRecursive($targetDir)) {
                        throw new Exception('An error occurred while finalizing the task, and the target directory ' . $targetDir . ' could not be cleaned');
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
