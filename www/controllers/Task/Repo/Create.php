<?php

namespace Controllers\Task\Repo;

use Exception;

class Create
{
    use \Controllers\Task\Param;
    use Package\Sync;
    use Package\Sign;
    use Metadata\Create;
    use Finalize;

    private $repo;
    private $rpmRepoController;
    private $debRepoController;
    private $task;
    private $repoSnapshotController;
    private $repoEnvController;
    private $taskLogStepController;
    private $taskLogSubStepController;
    private $type;
    private $packagesToSign = null;

    public function __construct(string $taskId)
    {
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
         *  Check and set task parameters
         */
        $requiredParams = array('package-type', 'repo-type', 'arch');
        $optionalParams = array('env', 'group', 'description', 'package-include', 'package-exclude');

        /**
         *  Required parameters in case the repo type is 'rpm'
         */
        if ($taskParams['package-type'] == 'rpm') {
            $requiredParams[] = 'releasever';
        }

        /**
         *  Required parameters in case the repo type is 'deb'
         */
        if ($taskParams['package-type'] == 'deb') {
            $requiredParams[] = 'dist';
            $requiredParams[] = 'section';
        }

        /**
         *  Required parameters in case the task is a mirror
         */
        if ($taskParams['repo-type'] == 'mirror') {
            $requiredParams[] = 'source';
            $requiredParams[] = 'gpg-check';
            $requiredParams[] = 'gpg-sign';
        }

        /**
         *  Required parameters in case the task is a local repo
         */
        if ($taskParams['repo-type'] == 'local') {
            $this->repo->setName($taskParams['alias']);
        }

        $this->taskParamsCheck('Create repo', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, $optionalParams);

        if ($taskParams['repo-type'] == 'mirror') {
            /**
             *  Alias parameter can be empty, if it's the case, the value will be 'source'
             */
            if (!empty($taskParams['alias'])) {
                $this->repo->setName($taskParams['alias']);
            } else {
                $this->repo->setName($this->repo->getSource());
            }
        }

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('create');

        /**
         *  Start task
         */
        $this->task->setDate(date('Y-m-d'));
        $this->task->setTime(date('H:i:s'));
        $this->task->updateDate($taskId, $this->task->getDate());
        $this->task->updateTime($taskId, $this->task->getTime());
        $this->task->start();

        /**
         *  Set repo type for the task to be executed
         */
        $this->type = $taskParams['repo-type'];
    }

    /**
     *  Create repository
     */
    public function execute()
    {
        if ($this->type == 'mirror') {
            $this->mirror();
        }

        if ($this->type == 'local') {
            $this->local();
        }
    }

    /**
     *  Create a mirror repository
     */
    private function mirror()
    {
        /**
         *  Define the date and time of the new mirror snapshot
         */
        $this->repo->setDate(date('Y-m-d'));
        $this->repo->setTime(date('H:i'));

        try {
            /**
             *  Sync packages
             */
            $this->syncPackage();

            /**
             *  Sign repository / packages
             */
            $this->signPackage();

            /**
             *  Create repository and symlinks
             */
            $this->createMetadata();

            /**
             *  Finalize repository (add to database and set permissions)
             */
            $this->finalize();

            /**
             *  Set task status to 'done'
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

    /**
     *  Create a local repository
     */
    private function local()
    {
        /**
         *  Set today date and time as target date and time
         */
        $this->repo->setDate(date('Y-m-d'));
        $this->repo->setTime(date("H:i"));

        try {
            $this->taskLogStepController->new('create-repo', 'CREATING REPOSITORY');
            $this->taskLogSubStepController->new('create-dirs', 'CREATING DIRECTORIES');

            /**
             *  Check if a repo/section with the same name is already active with snapshots
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($this->repo->isActive($this->repo->getName())) {
                    throw new Exception('<span class="label-white">' . $this->repo->getName() . '</span> repository already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($this->repo->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection())) {
                    throw new Exception('<span class="label-white">' . $this->repo->getName() . ' ❯ ' . $this->repo->getDist() . ' ❯ ' . $this->repo->getSection() . '</span> repository already exists');
                }
            }

            /**
             *  Create repo directory and subdirectories
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/packages')) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/packages', 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName() . '/packages');
                    }
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (!is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection())) {
                    if (!mkdir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection(), 0770, true)) {
                        throw new Exception('Could not create directory ' . REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection() . '/pool/' . $this->repo->getSection());
                    }
                }
            }

            /**
             *  Create environment symlink, if an environment has been specified
             */
            if (!empty($this->repo->getEnv())) {
                foreach ($this->repo->getEnv() as $env) {
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

                    unset($targetFile, $link);
                }
            }

            $this->taskLogSubStepController->completed();

            $this->taskLogSubStepController->new('updating-database', 'UPDATING DATABASE');

            /**
             *  Check if repository already exists in database
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $exists = $this->rpmRepoController->exists($this->repo->getName(), $this->repo->getReleasever());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $exists = $this->debRepoController->exists($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection());
            }

            /**
             *  If no repo of this name exists in database then we add it
             *  Note: here we set the source as $this->repo->getName()
             */
            if ($exists === false) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->rpmRepoController->add($this->repo->getName(), $this->repo->getReleasever(), $this->repo->getName());

                    /**
                     *  Retrieve repo Id from the last insert row
                     */
                    $this->repo->setRepoId($this->rpmRepoController->getLastInsertRowID());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $this->debRepoController->add($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection(), $this->repo->getName());

                    /**
                     *  Retrieve repo Id from the last insert row
                     */
                    $this->repo->setRepoId($this->debRepoController->getLastInsertRowID());
                }

            /**
             *  Else if a repo of this name exists, we attach this new snapshot and this new env to this repo
             */
            } else {
                /**
                 *  Retrieve and set repo Id from database
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $repoId = $this->rpmRepoController->getIdByNameReleasever($this->repo->getName(), $this->repo->getReleasever());
                }

                if ($this->repo->getPackageType() == 'deb') {
                    $repoId = $this->debRepoController->getIdByNameDistComponent($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection());
                }

                if (empty($repoId)) {
                    throw new Exception('Could not retrieve repository Id from database');
                }

                $this->repo->setRepoId($repoId);
            }

            unset($exists, $repoId);

            /**
             *  Add snapshot to database
             */
            $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), 'false', $this->repo->getArch(), array(), array(), array(), $this->repo->getType(), 'active', $this->repo->getRepoId());

            /**
             *  Retrieve snapshot Id from the last insert row
             */
            $this->repo->setSnapId($this->repo->getLastInsertRowID());

            /**
             *  Add env to database if an env has been specified by the user
             */
            if (!empty($this->repo->getEnv())) {
                foreach ($this->repo->getEnv() as $env) {
                    $this->repoEnvController->add($env, $this->repo->getDescription(), $this->repo->getSnapId());
                }
            }

            $this->taskLogSubStepController->completed();

            $this->taskLogSubStepController->new('applying-permissions', 'APPLYING PERMISSIONS');

            /**
             *  Apply permissions on the new repo
             */
            if ($this->repo->getPackageType() == 'rpm') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName(), WWW_USER, 'repomanager');
            }
            if ($this->repo->getPackageType() == 'deb') {
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName(), 'dir', 770);
                \Controllers\Filesystem\File::recursiveChmod(REPOS_DIR . '/' . $this->repo->getName(), 'file', 660);
                \Controllers\Filesystem\File::recursiveChown(REPOS_DIR . '/' . $this->repo->getName(), WWW_USER, 'repomanager');
            }

            $this->taskLogSubStepController->completed();

            /**
             *  Add repo to group if a group has been specified
             */
            if (!empty($this->repo->getGroup())) {
                $this->taskLogStepController->new('add-to-group', 'ADDING REPOSITORY TO GROUP');
                $this->repo->addRepoIdToGroup($this->repo->getRepoId(), $this->repo->getGroup());
                $this->taskLogStepController->completed();
            }

            $this->taskLogSubStepController->new('cleaning', 'CLEANING');

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            $this->taskLogSubStepController->completed();
            $this->taskLogStepController->completed();

            /**
             *  Set task status to 'done'
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
