<?php

namespace Controllers\Task;

use Exception;

/**
 *  Validate tasks parameters, then execute (or schedule) the tasks
 *  Single entry point of the web interface and of the API, so that a task is always validated the same way
 *  (permissions, protected environments, parameters...) whatever it comes from
 */
class Launcher
{
    /**
     *  Validate and execute tasks on fixed repositories, snapshots or mirrors
     *  Return the Id of the task, or the Id of the parent task grouping them when several tasks are executed
     */
    public function launch(array $tasksParams): int
    {
        if (empty($tasksParams)) {
            throw new Exception('No task to execute');
        }

        (new Form\Form())->validate($tasksParams);

        return $this->execute($tasksParams);
    }

    /**
     *  Validate and schedule a task targeting a dynamic set of repositories (all latest snapshots matching filters)
     *  Return the Id of the scheduled task
     */
    public function launchTarget(array $taskParams): int
    {
        $taskParams = (new Form\Form())->validateTarget($taskParams);

        return $this->execute([$taskParams]);
    }

    /**
     *  Execute validated tasks
     */
    private function execute(array $tasksParams): int
    {
        $taskId = (new Task())->execute($tasksParams);

        // Several tasks are grouped under a parent task, so a single Id is always expected
        if (!is_int($taskId)) {
            throw new Exception('Unexpected tasks execution result');
        }

        return $taskId;
    }
}
