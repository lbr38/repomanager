<?php

namespace Controllers\Task\Repo;

use Exception;

class Delete
{
    use \Controllers\Task\Param;

    private $repo;
    private $task;
    private $log;

    public function __construct(string $poolId, array $taskParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->task->getPid());

        /**
         *  Check and set operation parameters
         */
        $requiredParams = array('snapId');
        $this->taskParamsCheck('Delete repo snapshot', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Set operation details
         */
        $this->task->setAction('delete');
        $this->task->setType('manual');
        $this->task->setPoolId($poolId);
        $this->task->setTargetSnapId($this->repo->getSnapId());
        $this->task->setLogfile($this->log->getName());
        $this->task->start();
    }

    /**
     *  Delete a repo snapshot
     */
    public function execute()
    {
        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Launch external script that will build the main log file from the small log files of each step
         */
        $this->log->runLogBuilder($this->task->getPid(), $this->log->getLocation());

        try {
            ob_start();

            /**
             *  Generate operation summary table
             */
            include(ROOT . '/templates/tables/op-delete.inc.php');

            $this->log->step('DELETING');

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
                throw new Exception('cannot delete snapshot of the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
            }

            $this->log->stepOK();

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
             *  Set operation status to 'done'
             */
            $this->task->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  Print a red error message in the log file
             */
            $this->log->stepError($e->getMessage());

            /**
             *  Set operation status to 'error'
             */
            $this->task->setStatus('error');
            $this->task->setError($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->task->getDuration();

        /**
         *  Close operation
         */
        $this->log->stepDuration($duration);
        $this->task->close();
    }
}
