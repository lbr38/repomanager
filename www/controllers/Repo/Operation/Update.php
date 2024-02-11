<?php

namespace Controllers\Repo\Operation;

use Exception;

class Update extends Operation
{
    use Package\Sync;
    use Package\Sign;
    use Metadata\Create;
    use Finalize;

    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Update repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetGpgCheck', 'targetGpgResign', 'targetArch', 'onlySyncDifference');
        $optionnalParams = array('targetEnv', 'targetPackageTranslation');
        $this->operationParamsCheck('Update repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, $optionnalParams);

        /**
         *  Set operation details
         */
        $this->operation->setAction('update');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setGpgCheck($this->repo->getTargetGpgCheck());
        $this->operation->setGpgResign($this->repo->getTargetGpgResign());
        $this->operation->setLogfile($this->log->getName());

        /**
         *  If a schedule Id has been specified then it means that the action has been initialized by a schedule
         */
        if (!empty($operationParams['planId'])) {
            $this->operation->setType('plan');
            $this->operation->setPlanId($operationParams['planId']);
        }

        $this->operation->start();
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
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

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
            $this->operation->setStatus('done');
        } catch (\Exception $e) {
            /**
             *  Print a red error message in the log file
             */
            $this->log->stepError($e->getMessage());

            /**
             *  Set operation status to error
             */
            $this->operation->setStatus('error');
            $this->operation->setError($e->getMessage());

            /**
             *  Get total duration
             */
            $duration = $this->operation->getDuration();

            /**
             *  Close operation
             */
            $this->log->stepDuration($duration);
            $this->operation->close();

            throw new Exception($e->getMessage());
        }

        /**
         *  Get total duration
         */
        $duration = $this->operation->getDuration();

        /**
         *  Close operation
         */
        $this->log->stepDuration($duration);
        $this->operation->close();
    }
}
