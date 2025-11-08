<?php

namespace Controllers\Task\Repo;

use Exception;

class Rebuild extends \Controllers\Task\Execution
{
    use Package\Sign;
    use Metadata\Create;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'rebuild');
    }

    /**
     *  Rebuild repository metadata
     */
    public function execute()
    {
        /**
         *  Set snapshot metadata rebuild state in database
         */
        $this->repoController->snapSetRebuild($this->repoController->getSnapId(), 'running');

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
            $this->repoController->snapSetSigned($this->repoController->getSnapId(), $this->repoController->getGpgSign());

            /**
             *  Set snapshot metadata rebuild state in database
             */
            $this->repoController->snapSetRebuild($this->repoController->getSnapId(), '');

            /**
             *  Set task status to done
             */
            $this->taskController->setStatus('done');
            $this->taskController->updateStatus($this->taskId, 'done');
        } catch (Exception $e) {
            // Set sub step error message and mark step as error
            $this->taskLogSubStepController->error($e->getMessage());
            $this->taskLogStepController->error();

            // Set step error message
            $this->taskLogStepController->error($e->getMessage());

            // Set task status to error
            $this->taskController->setStatus('error');
            $this->taskController->updateStatus($this->taskId, 'error');
            $this->taskController->setError('Failed');
        }
    }
}
