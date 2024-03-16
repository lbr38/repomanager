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

    private $repo;
    private $task;
    private $log;

    public function __construct(string $poolId, array $taskParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->task->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->taskParamsCheck('Update repo', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetGpgCheck', 'targetGpgResign', 'targetArch', 'onlySyncDifference');
        $optionnalParams = array('targetEnv', 'targetPackageTranslation');
        $this->taskParamsCheck('Update repo', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, $optionnalParams);

        /**
         *  Set operation details
         */
        $this->task->setAction('update');
        $this->task->setType('immediate');
        $this->task->setPoolId($poolId);
        $this->task->setTargetSnapId($this->repo->getSnapId());
        $this->task->setGpgCheck($this->repo->getTargetGpgCheck());
        $this->task->setGpgResign($this->repo->getTargetGpgResign());
        $this->task->setLogfile($this->log->getName());

        /**
         *  If a schedule Id has been specified then it means that the action has been initialized by a schedule
         */
        if (!empty($taskParams['planId'])) {
            $this->task->setType('scheduled');
            $this->task->setPlanId($taskParams['planId']);
        }

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
        $this->repo->setTargetDate(date('Y-m-d'));
        $this->repo->setTargetTime(date('H:i'));

        /**
         *  Clear cache
         */
        \Controllers\App\Cache::clear();

        /**
         *  Launch external script that will build the main log file from the small log files of each step
         */
        $this->log->runLogBuilder($this->task->getPid(), $this->log->getLocation());

        try {
            /**
             *  Print operation details
             */
            $this->printDetails('UPDATE REPO');

            /**
             *  Sync packages
             */
            $this->syncPackage();

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
             *  Set operation status to done
             */
            $this->task->setStatus('done');
        } catch (Exception $e) {
            /**
             *  Print a red error message in the log file
             */
            $this->log->stepError($e->getMessage());

            /**
             *  Set operation status to error
             */
            $this->task->setStatus('error');
            $this->task->setError($e->getMessage());

            /**
             *  Get total duration
             */
            $duration = $this->task->getDuration();

            /**
             *  Close operation
             */
            $this->log->stepDuration($duration);
            $this->task->close();

            throw new Exception($e->getMessage());
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
