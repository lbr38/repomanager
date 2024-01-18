<?php

namespace Controllers\Repo\Operation;

use Exception;

class RemoveEnv extends Operation
{
    public function __construct(string $poolId = '00000', array $operationParams)
    {
        /**
         *  Only admin can remove repo snapshot environment
         */
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $this->repo = new \Controllers\Repo\Repo();
        $this->operation = new \Controllers\Operation\Operation();
        $this->log = new \Controllers\Log\OperationLog('repomanager', $this->operation->getPid());

        /**
         *  Check and set snapId parameter
         */
        $requiredParams = array('repoId', 'snapId', 'envId');
        $this->operationParamsCheck('Remove repo snapshot environment', $operationParams, $requiredParams);
        $this->operationParamsSet($operationParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById($this->repo->getRepoId(), $this->repo->getSnapId(), $this->repo->getEnvId());

        /**
         *  Set operation details
         */
        $this->operation->setAction('removeEnv');
        $this->operation->setType('manual');

        /**
         *  This operation type does not have a real poolId because it is executed outside the usual process
         */
        $this->operation->setPoolId('00000');
        $this->operation->setTargetSnapId($this->repo->getSnapId());
        $this->operation->setTargetEnvId($this->repo->getEnv());
        $this->operation->setLogfile($this->log->getName());
        $this->operation->start();
    }

    /**
     *  Remove snapshot environment
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

        try {
            ob_start();

            /**
             *  Generate operation summary table
             */
            include(ROOT . '/templates/tables/op-remove-env.inc.php');

            $this->log->step('DELETING');

            /**
             *  Delete environment symlink
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (file_exists(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv())) {
                    unlink(REPOS_DIR . '/' . $this->repo->getName() . '_' . $this->repo->getEnv());
                }
            }
            if ($this->repo->getPackageType() == 'deb') {
                if (file_exists(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv())) {
                    unlink(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '_' . $this->repo->getEnv());
                }
            }

            /**
             *  Delete environment from database
             */
            $this->repo->removeEnv($this->repo->getEnvId());

            $this->log->stepOK();

            /**
             *  Automatic cleaning of unused snapshots
             */
            $snapshotsRemoved = $this->repo->cleanSnapshots();

            if (!empty($snapshotsRemoved)) {
                $this->log->step('CLEANING');
                $this->log->stepOK($snapshotsRemoved);
            }

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

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
