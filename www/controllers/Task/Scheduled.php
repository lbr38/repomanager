<?php

namespace Controllers\Task;

use Exception;
use JsonException;
use Controllers\Task\Form\Param\Schedule;
use Controllers\Task\Listing as TaskListing;

class Scheduled extends Task
{
    private $taskListingController;

    public function __construct()
    {
        parent::__construct();

        $this->taskListingController = new TaskListing();
    }

    /**
     *  Get scheduled tasks by snapshot Id
     *  It returns an array of scheduled tasks using the given snapshot Id
     */
    public function getBySnapId(int $id) : array
    {
        $tasksUsingSnapId = [];

        foreach ($this->taskListingController->getScheduled() as $scheduledTask) {
            try {
                $taskParams = json_decode($scheduledTask['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Cannot decode scheduled task #' . $scheduledTask['Id'] . ' parameters: ' . $e->getMessage());
            }

            // A scheduled task grouping several tasks is using this snapshot if one of its sub-tasks is using it
            $tasksParams = !empty($taskParams['tasks']) ? $taskParams['tasks'] : [$taskParams];

            foreach ($tasksParams as $params) {
                if (!empty($params['snap-id']) and $params['snap-id'] == $id) {
                    $tasksUsingSnapId[] = $scheduledTask;
                    break;
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
        $this->deleteMatching(function (array $taskParams) use ($id): bool {
            return !empty($taskParams['snap-id']) and $taskParams['snap-id'] == $id;
        });
    }

    /**
     *  Delete scheduled tasks of a mirror
     */
    public function deleteByMirrorId(int $id) : void
    {
        $this->deleteMatching(function (array $taskParams) use ($id): bool {
            return ($taskParams['domain'] ?? '') == 'mirror' and ($taskParams['mirror-id'] ?? '') == $id;
        });
    }

    /**
     *  Delete scheduled tasks using a snapshot of a mirror
     */
    public function deleteByMirrorSnapId(int $id) : void
    {
        $this->deleteMatching(function (array $taskParams) use ($id): bool {
            return ($taskParams['domain'] ?? '') == 'mirror' and ($taskParams['mirror-snap-id'] ?? '') == $id;
        });
    }

    /**
     *  Delete the scheduled tasks matching the specified condition
     *  A scheduled task grouping several tasks (e.g. several snapshots to update or environments to point) only loses
     *  its matching sub-tasks, it is deleted if no sub-task remains
     */
    private function deleteMatching(callable $matches) : void
    {
        foreach ($this->taskListingController->getScheduled() as $scheduledTask) {
            try {
                $taskParams = json_decode($scheduledTask['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $e) {
                throw new Exception('Cannot decode scheduled task #' . $scheduledTask['Id'] . ' parameters: ' . $e->getMessage());
            }

            if (empty($taskParams['tasks'])) {
                if ($matches($taskParams)) {
                    $this->model->delete($scheduledTask['Id']);
                }

                continue;
            }

            $subTasks = array_values(array_filter($taskParams['tasks'], function (array $subTaskParams) use ($matches): bool {
                return !$matches($subTaskParams);
            }));

            // No sub-task matches
            if (count($subTasks) == count($taskParams['tasks'])) {
                continue;
            }

            if (empty($subTasks)) {
                $this->model->delete($scheduledTask['Id']);
                continue;
            }

            $taskParams['tasks'] = $subTasks;

            try {
                $this->model->updateRawParams($scheduledTask['Id'], json_encode($taskParams, JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                throw new Exception('Could not encode scheduled task #' . $scheduledTask['Id'] . ' parameters: ' . $e->getMessage());
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

            // The schedule type must be allowed for the action, tasks targeting a dynamic set of repositories accept any schedule type
            if (!\Controllers\Task\Target::isDynamic($taskRawParams)) {
                Schedule::checkAllowedType($taskRawParams['action'] ?? '', array_merge($task['schedule'], ['scheduled' => 'true']));
            }

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
