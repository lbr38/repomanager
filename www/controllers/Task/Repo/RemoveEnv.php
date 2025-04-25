<?php

namespace Controllers\Task\Repo;

use Exception;

class RemoveEnv
{
    use \Controllers\Task\Param;

    private $repo;
    private $task;
    private $repoSnapshotController;
    private $taskLogStepController;
    private $taskLogSubStepController;

    public function __construct(string $taskId)
    {
        $this->repo = new \Controllers\Repo\Repo();
        $this->task = new \Controllers\Task\Task();
        $this->repoSnapshotController = new \Controllers\Repo\Snapshot();
        $this->taskLogStepController = new \Controllers\Task\Log\Step($taskId);
        $this->taskLogSubStepController = new \Controllers\Task\Log\SubStep($taskId);

        /**
         *  Retrieve task params
         */
        $task = $this->task->getById($taskId);
        $taskParams = json_decode($task['Raw_params'], true);

        /**
         *  Check repo Id, snapshot Id and environment Id
         */
        $requiredParams = array('repo-id', 'snap-id', 'env-id');
        $this->taskParamsCheck('Remove repo snapshot environment', $taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById($taskParams['repo-id'], $taskParams['snap-id'], $taskParams['env-id']);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('removeEnv');

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
     *  Remove snapshot environment
     */
    public function execute()
    {
        try {
            $this->taskLogStepController->new('removing', 'REMOVING');

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

            $this->taskLogStepController->completed();

            $this->taskLogStepController->new('cleaning', 'CLEANING');

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Clean unused snapshots
             */
            try {
                $snapshotsRemoved = $this->repoSnapshotController->clean();
                $this->taskLogStepController->completed($snapshotsRemoved);
            } catch (Exception $e) {
                $this->taskLogStepController->error($e->getMessage());
            }

            /**
             *  Set task status to done
             */
            $this->task->setStatus('done');
            $this->task->updateStatus($this->task->getId(), 'done');
        } catch (Exception $e) {
            // Set sub step error message and mark step as error
            $this->taskLogSubStepController->error($e->getMessage());
            $this->taskLogStepController->error();

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
