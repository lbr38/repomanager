<?php

namespace Controllers\Task;

use Exception;
use JsonException;

class Scheduled extends Task
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     *  Get scheduled tasks by snapshot Id
     *  It returns an array of scheduled tasks using the given snapshot Id
     */
    public function getBySnapId(int $id) : array
    {
        $tasksUsingSnapId = [];

        // Get scheduled tasks
        $scheduledTasks = $this->listScheduled();

        if (!empty($scheduledTasks)) {
            foreach ($scheduledTasks as $scheduledTask) {
                try {
                    $taskParams = json_decode($scheduledTask['Raw_params'], true);
                } catch (JsonException $e) {
                    throw new Exception('Cannot decode scheduled task #' . $scheduledTask['Id'] . ' parameters: ' . $e->getMessage());
                }

                // If the scheduled task is using this snapshot, add it to the result
                if (!empty($taskParams['snap-id']) && $taskParams['snap-id'] == $id) {
                    $tasksUsingSnapId[] = $scheduledTask;
                }
            }
        }

        return $tasksUsingSnapId;
    }

    /**
     *  Delete scheduled tasks by snapshot Id
     */
    public function deleteBySnapId(int $id) : void
    {
        // Get scheduled tasks
        $scheduledTasks = $this->listScheduled();

        // Quit if there is no scheduled task
        if (empty($scheduledTasks)) {
            return;
        }

        foreach ($scheduledTasks as $scheduledTask) {
            try {
                $taskParams = json_decode($scheduledTask['Raw_params'], true);
            } catch (JsonException $e) {
                throw new Exception('Cannot decode scheduled task #' . $scheduledTask['Id'] . ' parameters: ' . $e->getMessage());
            }

            // If the scheduled task is using this snapshot, delete the task
            if (!empty($taskParams['snap-id']) && $taskParams['snap-id'] == $id) {
                $this->model->delete($scheduledTask['Id']);
            }
        }
    }
}
