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
    private $task;
    private $taskLog;
    private $type;

    public function __construct(string $taskId)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->taskLog = new \Controllers\Task\Log($taskId);

        /**
         *  Retrieve task params
         */
        $task = $this->task->getById($taskId);
        $taskParams = json_decode($task['Raw_params'], true);

        /**
         *  Check and set task parameters
         */
        $requiredParams = array('package-type', 'repo-type', 'arch');
        $optionnalParams = array('env', 'group', 'description');

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
        $this->taskParamsSet($taskParams, $requiredParams, $optionnalParams);

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
         *  Generate PID for the task
         */
        $this->task->generatePid();

        /**
         *  Generate log file
         */
        $this->taskLog->generateLog();

        /**
         *  Set PID
         */
        $this->task->updatePid($taskId, $this->task->getPid());

        /**
         *  Set log file location
         */
        $this->task->updateLogfile($taskId, $this->taskLog->getName());

        /**
         *  Start task
         */
        $this->task->updateDate($taskId, date('Y-m-d'));
        $this->task->updateTime($taskId, date('H:i:s'));
        $this->task->start($taskId, 'running');

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

        /**
         *  Run the external script that will build the main log file from the small log files of each step
         */
        $this->taskLog->runLogBuilder($this->task->getId(), $this->taskLog->getLocation());

        try {
            /**
             *  Print task details
             */
            $this->printDetails(strtoupper($this->repo->getPackageType()) . ' REPOSITORY MIRROR');

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
        } catch (\Exception $e) {
            $this->taskLog->stepError($e->getMessage());

            /**
             *  Set task status to 'error'
             */
            $this->task->setStatus('error');
            $this->task->updateStatus($this->task->getId(), 'error');
            $this->task->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->task->getDuration();

        /**
         *  End task
         */
        $this->taskLog->stepDuration($duration);
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

        /**
         *  Launch the external script that will build the main log file from the small log files of each step
         */
        $this->taskLog->runLogBuilder($this->task->getId(), $this->taskLog->getLocation());

        try {
            ob_start();

            /**
             *  Generate task summary table
             */
            include(ROOT . '/views/templates/tasks/new-local.inc.php');

            $this->taskLog->step('CREATING REPO');

            /**
             *  Check if a repo/section with the same name is already active with snapshots
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if ($myrepo->isActive($this->repo->getName())) {
                    throw new Exception('<span class="label-white">' . $this->repo->getName() . '</span> repository already exists');
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if ($myrepo->isActive($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection())) {
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
                if ($this->repo->getPackageType() == 'rpm') {
                    $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getName();
                    $link = REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv();
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $targetFile = $this->repo->getDateFormatted() . '_' . $this->repo->getSection();
                    $link = REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv();
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

            /**
             *  Check if repository already exists in database
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $exists = $this->repo->exists($this->repo->getName());
            }
            if ($this->repo->getPackageType() == 'deb') {
                $exists = $this->repo->exists($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection());
            }

            /**
             *  If no repo of this name exists in database then we add it
             *  Note: here we set the source as $this->repo->getName()
             */
            if ($exists === false) {
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->add($this->repo->getName(), 'rpm', $this->repo->getName());
                }
                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->add($this->repo->getName(), 'deb', $this->repo->getName());
                }

                /**
                 *  Retrieve repo Id from the last insert row
                 */
                $this->repo->setRepoId($this->repo->getLastInsertRowID());

                /**
                 *  Set repo releasever
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->updateReleasever($this->repo->getRepoId(), $this->repo->getReleasever());
                }

                /**
                 *  Set repo dist and section
                 */
                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->updateDist($this->repo->getRepoId(), $this->repo->getDist());
                    $this->repo->updateSection($this->repo->getRepoId(), $this->repo->getSection());
                }

            /**
             *  Else if a repo of this name exists, we attach this new snapshot and this new env to this repo
             */
            } else {
                /**
                 *  Retrieve and set repo Id from database
                 */
                if ($this->repo->getPackageType() == 'rpm') {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), '', ''));
                }

                if ($this->repo->getPackageType() == 'deb') {
                    $this->repo->setRepoId($this->repo->getIdByName($this->repo->getName(), $this->repo->getDist(), $this->repo->getSection()));
                }
            }

            unset($exists);

            /**
             *  Add snapshot to database
             */
            $this->repo->addSnap($this->repo->getDate(), $this->repo->getTime(), 'false', $this->repo->getArch(), array(), $this->repo->getType(), 'active', $this->repo->getRepoId());

            /**
             *  Retrieve snapshot Id from the last insert row
             */
            $this->repo->setSnapId($this->repo->getLastInsertRowID());

            /**
             *  Add env to database if an env has been specified by the user
             */
            if (!empty($this->repo->getEnv())) {
                $this->repo->addEnv($this->repo->getEnv(), $this->repo->getDescription(), $this->repo->getSnapId());
            }

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

            $this->taskLog->stepOK();

            /**
             *  Add repo to group if a group has been specified
             */
            if (!empty($this->repo->getGroup())) {
                $this->taskLog->step('ADDING TO GROUP');
                $this->repo->addRepoIdToGroup($this->repo->getRepoId(), $this->repo->getGroup());
                $this->taskLog->stepOK();
            }

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Set task status to 'done'
             */
            $this->task->setStatus('done');
            $this->task->updateStatus($this->task->getId(), 'done');
        } catch (\Exception $e) {
            /**
             *  Print a red error message in the log file
             */
            $this->taskLog->stepError($e->getMessage());

            /**
             *  Set task status to 'error'
             */
            $this->task->setStatus('error');
            $this->task->updateStatus($this->task->getId(), 'error');
            $this->task->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->task->getDuration();

        /**
         *  End task
         */
        $this->taskLog->stepDuration($duration);
        $this->task->end();
    }
}
