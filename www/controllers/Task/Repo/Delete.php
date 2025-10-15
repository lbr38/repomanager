<?php

namespace Controllers\Task\Repo;

use Exception;

class Delete
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
         *  Check snap Id parameter
         */
        $requiredParams = array('snap-id');
        $this->taskParamsCheck('Delete repository snapshot', $taskParams, $requiredParams);

        /**
         *  Getting all repo details from its snapshot Id
         */
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('delete');

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
     *  Delete a repo snapshot
     */
    public function execute()
    {
        $deleteResult = true;

        try {
            $this->taskLogStepController->new('deleting', 'DELETING');
            $this->taskLogSubStepController->new('deleting', 'DELETING REPOSITORY SNAPSHOT');

            /**
             *  Check that repository snapshot still exists
             */
            if (!$this->repo->existsSnapId($this->repo->getSnapId())) {
                throw new Exception('<span class="label-black">' . $this->repo->getDateFormatted() . '</span> repository snapshot does not exist anymore');
            }

            /**
             *  Define snapshot directory
             */
            if ($this->repo->getPackageType() == 'rpm') {
                $snapshotPath = REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever() . '/' . $this->repo->getDate();
            }

            if ($this->repo->getPackageType() == 'deb') {
                $snapshotPath = REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection() . '/' . $this->repo->getDate();
            }

            /**
             *  Delete snapshot directory
             */
            if (is_dir($snapshotPath)) {
                $deleteResult = \Controllers\Filesystem\Directory::deleteRecursive($snapshotPath);
            }

            if (!$deleteResult) {
                throw new Exception('Cannot delete <span class="label-black">' . $this->repo->getDateFormatted() . ' snapshot</span>');
            }

            $this->taskLogSubStepController->completed();

            $this->taskLogSubStepController->new('updating-database', 'UPDATING DATABASE');

            /**
             *  Set snapshot status to 'deleted' in database
             */
            $this->repoSnapshotController->updateStatus($this->repo->getSnapId(), 'deleted');

            $this->taskLogSubStepController->completed();

            $this->taskLogSubStepController->new('cleaning', 'CLEANING');

            /**
             *  Retrieve env Ids pointing to this snapshot
             */
            $envIds = $this->repo->getEnvIdBySnapId($this->repo->getSnapId());

            /**
             *  Process each env Id pointing to this snapshot
             */
            if (!empty($envIds)) {
                foreach ($envIds as $envId) {
                    /**
                     *  Delete env pointing to this snapshot in database
                     */
                    $myrepo = new \Controllers\Repo\Repo();
                    $myrepo->getAllById('', '', $envId);

                    if ($myrepo->getPackageType() == 'rpm') {
                        $link = REPOS_DIR . '/rpm/' . $myrepo->getName() . '/' . $myrepo->getReleasever() . '/' . $myrepo->getEnv();
                    }

                    if ($myrepo->getPackageType() == 'deb') {
                        $link = REPOS_DIR . '/deb/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '/' . $myrepo->getEnv();
                    }

                    /**
                     *  If a symbolic link of this environment pointed to the deleted snapshot then we can delete the symbolic link.
                     */
                    if (is_link($link)) {
                        if (readlink($link) == $myrepo->getDate()) {
                            if (!unlink($link)) {
                                throw new Exception('Could not remove existing symlink ' . $link);
                            }
                        }
                    }

                    unset($myrepo);
                }
            }

            /**
             *  Clean unused repos in groups
             */
            $this->repo->cleanGroups();

            /**
             *  Delete the parent directories if they are empty
             */
            if ($this->repo->getPackageType() == 'rpm') {
                if (\Controllers\Filesystem\Directory::isEmpty(REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever())) {
                    \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/rpm/' . $this->repo->getName() . '/' . $this->repo->getReleasever());
                }

                if (\Controllers\Filesystem\Directory::isEmpty(REPOS_DIR . '/rpm/' . $this->repo->getName())) {
                    \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/rpm/' . $this->repo->getName());
                }
            }

            if ($this->repo->getPackageType() == 'deb') {
                if (\Controllers\Filesystem\Directory::isEmpty(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection())) {
                    \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getSection());
                }

                if (\Controllers\Filesystem\Directory::isEmpty(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist())) {
                    \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/deb/' . $this->repo->getName() . '/' . $this->repo->getDist());
                }

                if (\Controllers\Filesystem\Directory::isEmpty(REPOS_DIR . '/deb/' . $this->repo->getName())) {
                    \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/deb/' . $this->repo->getName());
                }
            }

            $this->taskLogSubStepController->completed();
            $this->taskLogStepController->completed();

            /**
             *  Set task status to 'done'
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
        $duration = \Controllers\Utils\Convert::microtimeToHuman($this->task->getDuration());

        /**
         *  End task
         */
        $this->taskLogStepController->new('duration', 'DURATION');
        $this->taskLogStepController->none('Total duration: ' . $duration);
        $this->task->end();
    }
}
