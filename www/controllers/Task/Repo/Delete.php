<?php

namespace Controllers\Task\Repo;

use Exception;

class Delete
{
    use \Controllers\Task\Param;

    private $repo;
    private $task;
    private $taskLogStepController;
    private $taskLogSubStepController;

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
        try {
            $this->taskLogStepController->new('deleting', 'DELETING');

            /**
             *  Check that repository snapshot still exists
             */
            if (!$this->repo->existsSnapId($this->repo->getSnapId())) {
                throw new Exception('<span class="label-black">' . $this->repo->getDateFormatted() . '</span> repository snapshot does not exist anymore');
            }

            /**
             *  Delete snapshot
             */
            if ($this->repo->getPackageType() == "rpm") {
                if (is_dir(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName())) {
                    $deleteResult = \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getName());
                }
            }
            if ($this->repo->getPackageType() == "deb") {
                if (is_dir(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection())) {
                    $deleteResult = \Controllers\Filesystem\Directory::deleteRecursive(REPOS_DIR . '/' . $this->repo->getName() . '/' . $this->repo->getDist() . '/' . $this->repo->getDateFormatted() . '_' . $this->repo->getSection());
                }
            }

            if (isset($deleteResult) && $deleteResult !== true) {
                throw new Exception('Cannot delete snapshot of the <span class="label-black">' . $this->repo->getDateFormatted() . '</span>');
            }

            $this->taskLogStepController->completed();

            /**
             *  Set snapshot status to 'deleted' in database
             */
            $this->repo->snapSetStatus($this->repo->getSnapId(), 'deleted');

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

                    /**
                     *  If a symbolic link of this environment pointed to the deleted snapshot then we can delete the symbolic link.
                     */
                    if ($myrepo->getPackageType() == 'rpm') {
                        if (is_link(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv()) == $myrepo->getDateFormatted() . '_' . $myrepo->getName()) {
                                unlink(REPOS_DIR . '/' . $myrepo->getName() . '_' . $myrepo->getEnv());
                            }
                        }
                    }
                    if ($myrepo->getPackageType() == 'deb') {
                        if (is_link(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv())) {
                            if (readlink(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv()) == $myrepo->getDateFormatted() . '_' . $myrepo->getSection()) {
                                unlink(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '_' . $myrepo->getEnv());
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
        $duration = \Controllers\Common::convertMicrotime($this->task->getDuration());

        /**
         *  End task
         */
        $this->taskLogStepController->new('duration', 'DURATION');
        $this->taskLogStepController->none('Total duration: ' . $duration);
        $this->task->end();
    }
}
