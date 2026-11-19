<?php

namespace Controllers\Service\Unit;

use Exception;

class TaskQueue extends \Controllers\Service\Service
{
    private $taskQueueController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->taskQueueController = new \Controllers\Task\Queue();
    }

    /**
     *  Safety net of the tasks queue, which is otherwise processed as tasks are created and ended:
     *  - mark as failed the running tasks whose process has disappeared, to free their slot
     *  - close the parent tasks whose sub-tasks have all ended but could not close them
     *  - start the queued tasks for which a slot is available (e.g. tasks left queued after a restart)
     */
    public function run() : void
    {
        // Quit if there was an error while loading general settings
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            return;
        }

        try {
            foreach ($this->taskQueueController->reconcile() as $taskId) {
                parent::log('Task #' . $taskId . ' has no running process anymore, it has been marked as failed');
            }
        } catch (Exception $e) {
            parent::logError('Error while checking running tasks: ' . $e->getMessage());
        }

        try {
            foreach ($this->taskQueueController->closeParents() as $taskId) {
                parent::log('Parent task #' . $taskId . ' had no ongoing sub-task anymore, it has been closed');
            }
        } catch (Exception $e) {
            parent::logError('Error while closing parent tasks: ' . $e->getMessage());
        }

        try {
            $this->taskQueueController->dispatch();
        } catch (Exception $e) {
            parent::logError('Error while starting queued tasks: ' . $e->getMessage());
        }
    }
}
