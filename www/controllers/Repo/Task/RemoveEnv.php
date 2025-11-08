<?php

namespace Controllers\Repo\Task;

use Exception;

class RemoveEnv extends \Controllers\Task\Execution
{
    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'removeEnv');

        // Execute the task
        try {
            $this->execute();
        } catch (Exception $e) {
            $this->status = 'error';
            $this->error = $e->getMessage();
        }

        // End the task
        $this->end();
    }

    /**
     *  Remove snapshot environment
     */
    public function execute()
    {
        $this->taskLogStepController->new('removing', 'REMOVING');
        $this->taskLogSubStepController->new('delete-symlink', 'DELETE SYMLINK');

        /**
         *  Define environment symlink
         */
        if ($this->repoController->getPackageType() == 'rpm') {
            $symlink = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getEnv();
        }

        if ($this->repoController->getPackageType() == 'deb') {
            $symlink = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getEnv();
        }

        /**
         *  Delete environment symlink
         */
        if (file_exists($symlink)) {
            if (!unlink($symlink)) {
                throw new Exception('Failed to delete symlink: ' . $symlink);
            }
        }

        $this->taskLogSubStepController->completed();

        /**
         *  Delete environment from database
         */
        $this->taskLogSubStepController->new('update-database', 'UPDATE DATABASE');
        $this->repoEnvController->remove($this->repoController->getEnvId());
        $this->taskLogSubStepController->completed();

        $this->taskLogStepController->completed();
        $this->taskLogStepController->new('cleaning', 'CLEANING');

        /**
         *  Clean unused repos in groups
         */
        $this->repoController->cleanGroups();

        /**
         *  Clean unused snapshots
         */
        try {
            $snapshotsRemoved = $this->repoSnapshotController->clean();
            $this->taskLogStepController->completed($snapshotsRemoved);
        } catch (Exception $e) {
            $this->taskLogStepController->error($e->getMessage());
        }
    }
}
