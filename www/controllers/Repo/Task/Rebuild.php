<?php

namespace Controllers\Repo\Task;

use Exception;

class Rebuild extends \Controllers\Task\Execution
{
    use \Controllers\Repo\Package\Sign;
    use \Controllers\Repo\Metadata\Create;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'rebuild');

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
     *  Rebuild repository metadata
     */
    public function execute()
    {
        /**
         *  Set snapshot metadata rebuild state in database
         */
        $this->repoController->snapSetRebuild($this->repoController->getSnapId(), 'running');

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
    }
}
