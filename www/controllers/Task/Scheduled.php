<?php

namespace Controllers\Task;

use Exception;
use JsonException;
use Controllers\Task\Form\Param\Schedule;

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

    /**
     *  Edit a scheduled task
     */
    public function edit(array $tasks): void
    {
        foreach ($tasks as $task) {
            if (empty($task['id'])) {
                throw new Exception('Task Id is missing');
            }

            // Check that the task exists
            if (!$this->exists($task['id'])) {
                throw new Exception('Task #' . $task['id'] . ' does not exist');
            }

            // Get task details
            $taskInfo = $this->getById($task['id']);

            // Check that task status is 'scheduled'
            if (!in_array($taskInfo['Status'], ['scheduled', 'disabled'])) {
                throw new Exception('Task #' . $task['id'] . ' is not a scheduled task');
            }

            // Decode task parameters
            try {
                $taskRawParams = json_decode($taskInfo['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Could not decode task #' . $task['id'] . ' parameters: ' . $e->getMessage());
            }

            // Check that the task is scheduled
            if (empty($taskRawParams['schedule']['scheduled']) || $taskRawParams['schedule']['scheduled'] != 'true') {
                throw new Exception('Task #' . $task['id'] . ' is not a scheduled task');
            }

            // Validate the schedule parameters and update the task
            Schedule::check($task['schedule']);

            // Clean the schedule parameters (remove unnecessary parameters depending on the schedule type)
            $scheduleCleaned = Schedule::clean([
                'schedule' => $task['schedule']
            ]);

            // Update the task with the new schedule parameters
            $taskRawParams['schedule'] = $scheduleCleaned['schedule'];

            try {
                $taskRawParamsJson = json_encode($taskRawParams, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Could not encode task #' . $task['id'] . ' parameters: ' . $e->getMessage());
            }

            // Update the task in the database
            $this->model->updateRawParams($task['id'], $taskRawParamsJson);
        }
    }
}
