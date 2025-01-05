<?php

namespace Controllers\Task\Repo;

use Exception;

class Update
{
    use \Controllers\Task\Param;
    use Package\Sync;
    use Package\Sign;
    use Metadata\Create;
    use Finalize;

    private $sourceRepo;
    private $repo;
    private $task;
    private $taskLogStepController;
    private $taskLogSubStepController;
    private $packagesToSign = null;

    public function __construct(string $taskId)
    {
        $this->sourceRepo = new \Controllers\Repo\Repo();
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
         *  If task is a scheduled task and it is recurring, then update the snap-id parameter to be the last snap-id
         *  If not, the task could try to update a repo with an old and possibly deleted snap-id
         */
        if ($taskParams['schedule']['scheduled'] == 'true') {
            if ($taskParams['schedule']['schedule-type'] == 'recurring') {
                /**
                 *  Retrieve repository latest snapshot Id, from the repo Id
                 */
                $latestSnapId = $this->repo->getLatestSnapId($taskParams['repo-id']);

                /**
                 *  Throw error id no snapshot is found
                 */
                if (empty($latestSnapId)) {
                    throw new Exception('Could not find latest snapshot Id for this repository');
                }

                /**
                 *  Update snap-id parameter
                 */
                $taskParams['snap-id'] = $latestSnapId;

                /**
                 *  Update raw_params in the database
                 */
                $this->task->updateRawParams($taskId, json_encode($taskParams));
            }
        }

        /**
         *  Check source repo snap Id parameter
         */
        $requiredParams = array('snap-id');
        $this->taskParamsCheck('Update repository', $taskParams, $requiredParams);

        /**
         *  Getting all source repo details from its snapshot Id
         *  Do the same for the actual repo to herit all source repo parameters
         */
        $this->sourceRepo->getAllById(null, $taskParams['snap-id'], null);
        $this->repo->getAllById(null, $taskParams['snap-id'], null);

        /**
         *  Override with parameters defined by the user
         */
        $requiredParams = array('gpg-check', 'gpg-sign', 'arch');
        $optionalParams = array('env', 'package-include', 'package-exclude');

        $this->taskParamsCheck('Update repo', $taskParams, $requiredParams);
        $this->taskParamsSet($taskParams, $requiredParams, $optionalParams);

        /**
         *  Prepare task and task log
         */

        /**
         *  Set task Id
         */
        $this->task->setId($taskId);
        $this->task->setAction('update');

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
     *  Update repository
     */
    public function execute()
    {
        /**
         *  Define default date and time
         */
        $this->repo->setDate(date('Y-m-d'));
        $this->repo->setTime(date('H:i'));

        try {
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
            $this->task->setError($e->getMessage());
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
