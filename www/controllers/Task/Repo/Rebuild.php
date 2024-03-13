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
        $this->taskParamsCheck('Rebuild repo metadata', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Set additionnal params from the actual repo to rebuild
         */
        $taskParams['targetDate'] = $this->repo->getDate();
        $taskParams['targetArch'] = $this->repo->getArch();

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetGpgResign', 'targetDate', 'targetArch');
        $this->taskParamsCheck('Rebuild repo', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, null);

        /**
         *  Set operation details
         */
        $this->task->setAction('rebuild');
        $this->task->setType('manual');
        $this->task->setPoolId($poolId);
        $this->task->setTargetSnapId($this->repo->getSnapId());
        $this->task->setGpgResign($this->repo->getTargetGpgResign());
        $this->task->setLogfile($this->log->getName());
        $this->task->start();
    }

    /**
     *  Rebuild repo metadata
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

        /**
         *  Set snapshot metadata rebuild state in database
         */
        $this->repo->snapSetRebuild($this->repo->getSnapId(), 'running');

        try {
            /**
             *  Print operation details
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
            $this->repo->snapSetSigned($this->repo->getSnapId(), $this->repo->getTargetGpgResign());

            /**
             *  Set snapshot metadata rebuild state in database
             */
            $this->repo->snapSetRebuild($this->repo->getSnapId(), '');

            /**
             *  Set operation status to done
             */
            $this->task->setStatus('done');
        } catch (\Exception $e) {
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
             *  Set snapshot metadata rebuild state in database
             */
            $this->repo->snapSetRebuild($this->repo->getSnapId(), 'failed');
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
