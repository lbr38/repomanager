<?php

namespace Controllers\Task\Repo;

use Exception;

class Rebuild
{
    use \Controllers\Task\Param;
    use Package\Sign;
    use Metadata\Create;

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
        $this->taskParamsCheck('Repo environment', $taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Check and set others task parameters
         */
        $requiredParams = array('gpg-sign');
        $this->taskParamsCheck('Rebuild repo', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, null);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('rebuild');

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
        $this->task->setDate(date('Y-m-d'));
        $this->task->setTime(date('H:i:s'));
        $this->task->updateDate($taskId, $this->task->getDate());
        $this->task->updateTime($taskId, $this->task->getTime());
        $this->task->start($taskId, 'running');
    }

    /**
     *  Rebuild repo metadata
     */
    public function execute()
    {
        /**
         *  Launch external script that will build the main log file from the small log files of each step
         */
        $this->taskLog->runLogBuilder($this->task->getId(), $this->taskLog->getLocation());

        /**
         *  Set snapshot metadata rebuild state in database
         */
        $this->repo->snapSetRebuild($this->repo->getSnapId(), 'running');

        try {
            /**
             *  Print task details
             */
            $this->printDetails('REBUILD REPO METADATA');

            /**
             *  Sign repository / packages
             */
            $this->signPackage();

            /**
             *  Create repository and symlinks
             */
            $this->createMetadata();

            /**
             *  Etape 4 : on modifie l'état de la signature du repo en BDD
             *  Set repo signature state in database
             *  As we have rebuilt the repo files, it is possible that we have switched from a signed repo to an unsigned repo, or vice versa, we must therefore modify the state in the database
             */
            $this->repo->snapSetSigned($this->repo->getSnapId(), $this->repo->getGpgSign());

            /**
             *  Set snapshot metadata rebuild state in database
             */
            $this->repo->snapSetRebuild($this->repo->getSnapId(), '');

            /**
             *  Set task status to done
             */
            $this->task->setStatus('done');
            $this->task->updateStatus($this->task->getId(), 'done');
        } catch (\Exception $e) {
            /**
             *  Print a red error message in the log file
             */
            $this->taskLog->stepError($e->getMessage());

            /**
             *  Set task status to error
             */
            $this->task->setStatus('error');
            $this->task->updateStatus($this->task->getId(), 'error');
            $this->task->setError($e->getMessage());

            /**
             *  Set snapshot metadata rebuild state in database
             */
            $this->repo->snapSetRebuild($this->repo->getSnapId(), 'failed');
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
