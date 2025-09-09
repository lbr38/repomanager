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
    private $taskLogStepController;
    private $taskLogSubStepController;
    private $packagesToSign = null;

    public function __construct(string $taskId)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);

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
         *  Start task
         */
        $this->task->setDate(date('Y-m-d'));
        $this->task->setTime(date('H:i:s'));
        $this->task->updateDate($taskId, $this->task->getDate());
        $this->task->updateTime($taskId, $this->task->getTime());
        $this->task->start();
    }

    /**
     *  Rebuild repo metadata
     */
    public function execute()
    {
        /**
         *  Set snapshot metadata rebuild state in database
         */
        $this->repo->snapSetRebuild($this->repo->getSnapId(), 'running');

        try {
            /**
             *  Sign repository / packages
             */
            $this->signPackage();

            /**
             *  Create repository and symlinks
             */
            $this->createMetadata();

            /**
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
        } catch (Exception $e) {
            // Set sub step error message and mark step as error
            $this->taskLogSubStepController->error($e->getMessage());
            $this->taskLogStepController->error();

            // Set step error message
            $this->taskLogStepController->error($e->getMessage());

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
