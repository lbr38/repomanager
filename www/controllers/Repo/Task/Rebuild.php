<?php

namespace Controllers\Repo\Task;

use Exception;

class Rebuild extends \Controllers\Task\Execution
{
    use \Controllers\Repo\Package\Sign;
    use \Controllers\Repo\Metadata\Create;
    use \Controllers\Repo\Task\Finalize;

    public function __construct(string $taskId)
    {
        parent::__construct($taskId, 'rebuild');

        // Execute the task
        try {
            $this->execute();
        } catch (Exception $e) {
            $this->status = 'error';
            $this->error = $e->getMessage();

            // Throw back the exception to be caught by the main script
            throw new Exception($e->getMessage());
        } finally {
            // End the task
            $this->end();
        }
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
         *  Finalize repository (update database, clean temporary files, etc.)
         */
        $this->finalize();
    }
}
