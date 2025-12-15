<?php

namespace Controllers\Repo\Task;

use \Controllers\Filesystem\Directory;
use Exception;

class Delete extends \Controllers\Task\Execution
{
    private $scheduledTaskController;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'delete');

        $this->scheduledTaskController = new \Controllers\Task\Scheduled();

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
     *  Delete a repository snapshot
     */
    public function execute()
    {
        $deleted = true;

        $this->taskLogStepController->new('deleting', 'DELETING');
        $this->taskLogSubStepController->new('deleting', 'DELETING REPOSITORY SNAPSHOT');

        // Check that repository snapshot still exists
        if (!$this->repoController->existsSnapId($this->repoController->getSnapId())) {
            throw new Exception('<span class="label-black">' . $this->repoController->getDateFormatted() . '</span> repository snapshot does not exist anymore');
        }

        // Define snapshot directory
        if ($this->repoController->getPackageType() == 'rpm') {
            $snapshotPath = REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever() . '/' . $this->repoController->getDate();
        }

        if ($this->repoController->getPackageType() == 'deb') {
            $snapshotPath = REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection() . '/' . $this->repoController->getDate();
        }

        // Delete snapshot directory
        if (is_dir($snapshotPath)) {
            $deleted = Directory::deleteRecursive($snapshotPath);
        }

        if (!$deleted) {
            throw new Exception('Cannot delete <span class="label-black">' . $this->repoController->getDateFormatted() . ' snapshot</span>');
        }

        $this->taskLogSubStepController->new('updating-database', 'UPDATING DATABASE');

        // Set snapshot status to 'deleted' in database
        $this->repoSnapshotController->updateStatus($this->repoController->getSnapId(), 'deleted');

        $this->taskLogSubStepController->new('cleaning', 'CLEANING');

        // Retrieve environment Ids pointing to this snapshot
        $envIds = $this->repoController->getEnvIdBySnapId($this->repoController->getSnapId());

        // Delete each environment pointing to this snapshot
        if (!empty($envIds)) {
            foreach ($envIds as $envId) {
                $repoController = new \Controllers\Repo\Repo();
                $repoController->getAllById('', '', $envId);

                if ($repoController->getPackageType() == 'rpm') {
                    $link = REPOS_DIR . '/rpm/' . $repoController->getName() . '/' . $repoController->getReleasever() . '/' . $repoController->getEnv();
                }

                if ($repoController->getPackageType() == 'deb') {
                    $link = REPOS_DIR . '/deb/' . $repoController->getName() . '/' . $repoController->getDist() . '/' . $repoController->getSection() . '/' . $repoController->getEnv();
                }

                // If a symbolic link of this environment pointed to the deleted snapshot then delete the symbolic link
                if (is_link($link)) {
                    if (readlink($link) == $repoController->getDate()) {
                        if (!unlink($link)) {
                            throw new Exception('Could not remove existing symlink ' . $link);
                        }
                    }
                }

                unset($repoController);
            }
        }

        // Clean unused repos in groups
        $this->repoController->cleanGroups();

        // Delete any scheduled tasks that were using this snapshot
        $this->scheduledTaskController->deleteBySnapId($this->repoController->getSnapId());

        // Delete the parent directories if they are empty
        if ($this->repoController->getPackageType() == 'rpm') {
            Directory::deleteIfEmpty([
                REPOS_DIR . '/rpm/' . $this->repoController->getName() . '/' . $this->repoController->getReleasever(),
                REPOS_DIR . '/rpm/' . $this->repoController->getName()
            ]);
        }

        if ($this->repoController->getPackageType() == 'deb') {
            Directory::deleteIfEmpty([
                REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist() . '/' . $this->repoController->getSection(),
                REPOS_DIR . '/deb/' . $this->repoController->getName() . '/' . $this->repoController->getDist(),
                REPOS_DIR . '/deb/' . $this->repoController->getName()
            ]);
        }

        $this->taskLogSubStepController->completed();
        $this->taskLogStepController->completed();
    }
}
