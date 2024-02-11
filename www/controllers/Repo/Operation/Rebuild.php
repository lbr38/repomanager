<?php

namespace Controllers\Repo\Operation;

use Exception;

class Rebuild extends Operation
{
    use Package\Sign;
    use Metadata\Create;

    public function __construct(string $poolId, array $operationParams)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('snapId');
        $this->operationParamsCheck('Rebuild repo metadata', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $this->repo->getSnapId(), null);

        /**
         *  Set additionnal params from the actual repo to rebuild
         */
        $operationParams['targetDate'] = $this->repo->getDate();
        $operationParams['targetArch'] = $this->repo->getArch();

        /**
         *  Check and set others operation parameters
         */
        $requiredParams = array('targetGpgResign', 'targetDate', 'targetArch');
        $this->operationParamsCheck('Rebuild repo', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams, null);

        /**
         *  Set operation details
         */
        $this->operation->setAction('rebuild');
        $this->operation->setType('manual');
        $this->operation->setPoolId($poolId);
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setGpgResign($this->repo->getTargetGpgResign());
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();
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
        $this->log->runLogBuilder($this->operation->getPid(), $this->log->getLocation());

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
             *  Set snapshot metadata rebuild state in database
             */
            $this->repo->snapSetRebuild($this->repo->getSnapId(), 'failed');
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
