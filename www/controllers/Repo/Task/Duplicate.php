<?php

namespace Controllers\Repo\Task;

use \Controllers\Filesystem\Directory;
use \Controllers\Filesystem\File;
use Exception;

class Duplicate extends \Controllers\Task\Execution
{
    use \Controllers\Repo\Metadata\Create;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'duplicate');

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
     *  Duplicate repository
     */
    public function execute()
    {
        $this->taskLogStepController->new('duplicating', 'DUPLICATING');
        $this->taskLogSubStepController->new('initializing', 'INITIALIZING');

        /**
         *  Check if source repo snapshot exists
         */
        if ($this->sourceRepoController->existsSnapId($this->sourceRepoController->getSnapId()) === false) {
            throw new Exception('Source repository snapshot does not exist');
        }

        /**
         *  Check if a repo with the same name already exists
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            if ($this->rpmRepoController->isActive($this->repoController->getName(), $this->repoController->getReleasever())) {
                throw new Exception('A repo <span class="label-black">' . $this->repoController->getName() . ' (release ver. ' . $this->repoController->getReleasever() . ')</span> already exists');
            }
        }
        if ($this->repoController->getPackageType() == 'deb') {
            if ($this->debRepoController->isActive($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection())) {
                throw new Exception('A repo <span class="label-black">' . $this->repoController->getName() . ' ❯ ' . $this->repoController->getDist() . ' ❯ ' . $this->repoController->getSection() . '</span> already exists');
            }
        }

        /**
         *  Define source snapshot path
         */
        if ($this->sourceRepoController->getPackageType() == 'rpm') {
            $sourceSnapshotPath = REPOS_DIR . '/rpm/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getReleasever() . '/' . $this->sourceRepoController->getDate();
        }
        if ($this->sourceRepoController->getPackageType() == 'deb') {
            $sourceSnapshotPath = REPOS_DIR . '/deb/' . $this->sourceRepoController->getName() . '/' . $this->sourceRepoController->getDist() . '/' . $this->sourceRepoController->getSection() . '/' . $this->sourceRepoController->getDate();
        }

        /**
         *  Define target snapshot path
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            $parentDir = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever();
            $targetSnapshotPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/'. $this->repoController->getDate();
        }
        if ($this->repoController->getPackageType() == 'deb') {
            $parentDir = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection();
            $targetSnapshotPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
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
        $tempDir = REPOS_DIR . '/temporary-task-' . $this->taskId;

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
        $dirs  = File::recursiveScan($sourceSnapshotPath, 'dir', true);
        $files = File::recursiveScan($sourceSnapshotPath, 'file', true);

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
            $files = File::findRecursive($targetSnapshotPath, ['completed'], true);

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
            if ($this->repoController->getPackageType() == 'deb') {
                $this->createMetadata();
            }

            $this->taskLogStepController->new('finalizing', 'FINALIZING');

            /**
             *  Create a symlink to the new repo, only if the user has specified an environment
             */
            if (!empty($this->repoController->getEnv())) {
                foreach ($this->repoController->getEnv() as $env) {
                    $this->taskLogSubStepController->new('pointing-environment', 'POINTING ENVIRONMENT');

                    if ($this->repoController->getPackageType() == 'rpm') {
                        $link = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $env;
                    }
                    if ($this->repoController->getPackageType() == 'deb') {
                        $link = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $env;
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
                    if (!symlink($this->repoController->getDate(), $link)) {
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
            if ($this->repoController->getPackageType() == 'rpm') {
                $this->rpmRepoController->add($this->repoController->getName(), $this->repoController->getReleasever(), $this->repoController->getSource());
                $targetRepoId = $this->rpmRepoController->getLastInsertRowID();
            }
            if ($this->repoController->getPackageType() == 'deb') {
                $this->debRepoController->add($this->repoController->getName(), $this->repoController->getDist(), $this->repoController->getSection(), $this->repoController->getSource());
                $targetRepoId = $this->debRepoController->getLastInsertRowID();
            }

            /**
             *  Add the new repo snapshot in database
             */
            $this->repoSnapshotController->add($this->repoController->getDate(), $this->repoController->getTime(), $this->repoController->getSigned(), $this->repoController->getArch(), array(), $this->repoController->getPackagesToInclude(), $this->repoController->getPackagesToExclude(), $this->repoController->getType(), $this->repoController->getStatus(), $targetRepoId);

            /**
             *  Retrieve the Id of the new repo snapshot in database
             */
            $targetSnapId = $this->repoSnapshotController->getLastInsertRowID();

            /**
             *  Add the new repo environment in database, only if the user has specified an environment
             */
            if (!empty($this->repoController->getEnv())) {
                foreach ($this->repoController->getEnv() as $env) {
                    $this->repoEnvController->add($env, $this->repoController->getDescription(), $targetSnapId);
                }
            }

            /**
             *  Add the new repo to a group if a group has been specified
             */
            if (!empty($this->repoController->getGroup())) {
                $this->repoController->addRepoIdToGroup($targetRepoId, $this->repoController->getGroup());
            }

            /**
             *  Clean unused repos in groups
             */
            $this->repoController->cleanGroups();

            $this->taskLogSubStepController->completed();
            $this->taskLogStepController->completed();

        /**
         *  If an error occurred after the temporary directory was renamed, clean the target directory
         */
        } catch (Exception $e) {
            if (file_exists($targetSnapshotPath)) {
                if (!Directory::deleteRecursive($targetSnapshotPath)) {
                    throw new Exception('An error occurred while finalizing the task, and the target directory ' . $targetSnapshotPath . ' could not be cleaned');
                }
            }

            /**
             *  Throw initial exception to set the task as error
             */
            throw new Exception($e->getMessage());
        }
    }
}
