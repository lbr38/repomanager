<?php

namespace Controllers\Task\Repo;

use Exception;

class Delete
{
    use \Controllers\Task\Param;

    private $repo;
    private $task;
    private $taskLog;

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
         *  Check snap Id parameter
         */
        $requiredParams = array('snap-id');
        $this->taskParamsCheck('Delete repository snapshot', $taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('delete');

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
    }

    /**
     *  Delete a repo snapshot
     */
    public function execute()
    {
        /**
         *  Launch external script that will build the main log file from the small log files of each step
         */
        $this->taskLog->runLogBuilder($this->task->getId(), $this->taskLog->getLocation());

        try {
            ob_start();

            /**
             *  Generate task summary table
             */
            include(ROOT . '/views/templates/tasks/delete.inc.php');

            $this->taskLog->step('DELETING');

            /**
             *  Check that repository snapshot still exists
             */
            if (!$this->repo->existsSnapId($this->repo->getSnapId())) {
                throw new Exception('<span class="label-black">' . $this->repo->getDateFormatted() . '</span> repository snapshot does not exist anymore');
            }

            /**
             *  Delete snapshot
             */
            if ($this->repo->getPackageType() == "rpm") {
                if (is_dir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName())) {
                    $deleteResult = \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName());
                }
            }
            if ($this->repo->getPackageType() == "deb") {
                if (is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection())) {
                    $deleteResult = \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection());
                }
            }

            if (isset($deleteResult) && $deleteResult !== true) {
                throw new Exception('Cannot delete snapshot of the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
            }

            $this->taskLog->stepOK();

            /**
             *  Set snapshot status to 'deleted' in database
             */
            $this->repo->snapSetStatus($this->repo->getSnapId(), 'deleted');

            /**
             *  Retrieve env Ids pointing to this snapshot
             */
            $envIds = $this->repo->getEnvIdBySnapId($this->repo->getSnapId());

            /**
             *  Process each env Id pointing to this snapshot
             */
            if (!empty($envIds)) {
                foreach ($envIds as $envId) {
                    /**
                     *  Delete env pointing to this snapshot in database
                     */
                    $myrepo = new \Controllers\Repo\Repo();
                    $myrepo->getAllById('', '', $envId);

                    /**
                     *  If a symbolic link of this environment pointed to the deleted snapshot then we can delete the symbolic link.
                     */
                    if ($myrepo->getPackageType() == 'rpm') {
                        if (is_link(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv()) == $myrepo->getDateFormatted() . '_' . $myrepo->getName()) {
                                unlink(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv());
                            }
                        }
                    }
                    if ($myrepo->getPackageType() == 'deb') {
                        if (is_link(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv()) == $myrepo->getDateFormatted() . '_' . $myrepo->getSection()) {
                                unlink(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv());
                            }
                        }
                    }
                    unset($myrepo);
                }
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
