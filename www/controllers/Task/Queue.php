<?php

namespace Controllers\Task;

use Controllers\Lock;
use Controllers\Process;
use Controllers\Settings;

/**
 *  Tasks queue
 *
 *  No process waits for its turn: dispatch() starts a process only for the queued tasks that can run now,
 *  and is called each time a slot may have been freed or a task queued (task created, ended, stopped or
 *  deleted, settings changed), and every minute by the service as a safety net.
 *
 *  A started process first claims its task (see claim()). Claims are serialized by a lock and re-check the
 *  queue, so the maximum number of simultaneous tasks is never exceeded and a task is never executed twice,
 *  even when several dispatches start a process for the same task at the same time.
 */
class Queue
{
    // A task grouping sub-tasks that is still without sub-task after this delay (seconds) failed to create them
    private const GROUPING_GRACE = 600;

    private $taskController;
    private $taskListingController;
    private $settingsController;

    public function __construct()
    {
        $this->taskController = new Task();
        $this->taskListingController = new Listing();
        $this->settingsController = new Settings();
    }

    /**
     *  Acquire the queue lock and return it
     *  No queued task can be claimed while it is held
     */
    public static function lock(): Lock
    {
        $lock = new Lock('tasks-queue');
        $lock->acquire();

        return $lock;
    }

    /**
     *  Start a process for each queued task that can run now, in queue order
     */
    public function dispatch(): void
    {
        $queuedTasks = $this->taskListingController->getExecutable('queued', true);

        if (empty($queuedTasks)) {
            return;
        }

        $maxSimultaneous = $this->getMaxSimultaneous();

        if ($maxSimultaneous !== null) {
            $slots = $maxSimultaneous - $this->countRunning();

            if ($slots <= 0) {
                return;
            }

            $queuedTasks = array_slice($queuedTasks, 0, $slots);
        }

        foreach ($queuedTasks as $task) {
            $this->spawn((int) $task['Id']);
        }
    }

    /**
     *  Claim a queued task for the current process, return true if the process must run it
     *  The task is claimed only if it is still queued and a slot is available for it in queue order.
     *  Must be called by the task process itself (tasks/execute.php).
     */
    public function claim(int $taskId): bool
    {
        $lock = self::lock();

        try {
            // The task may have been stopped, deleted, or claimed by another process in the meantime
            $position = array_search($taskId, array_column($this->taskListingController->getExecutable('queued', true), 'Id'));

            if ($position === false) {
                return false;
            }

            $maxSimultaneous = $this->getMaxSimultaneous();

            if ($maxSimultaneous !== null and $position >= $maxSimultaneous - $this->countRunning()) {
                return false;
            }

            /**
             *  The process is identified as the one running the task (see Task::getProcessPid()) before the task is
             *  marked as running, so that a running task always has a process
             */
            cli_set_process_title('repomanager.task-run.' . $taskId);

            // The task may have been stopped or deleted in the meantime, this is not protected by the lock
            if (!$this->taskController->updateStatusIf($taskId, 'queued', 'running')) {
                cli_set_process_title('repomanager.task-run');

                return false;
            }

            return true;
        } finally {
            $lock->release();
        }
    }

    /**
     *  Called when the process of a claimed task ends, whatever the way it ends
     *  A task that has not been ended properly (uncaught exception, fatal error...) is marked as failed, then
     *  the slot it used is given to the next queued task
     */
    public function release(int $taskId, string $error = ''): void
    {
        $this->taskController->abort($taskId, !empty($error) ? $error : 'The task process ended unexpectedly');

        // The process may have ended before closing the parent task (closing it again has no effect)
        $task = $this->taskController->getById($taskId);

        if (!empty($task['Parent_task_id'])) {
            $this->taskController->closeParent((int) $task['Parent_task_id']);
        }

        $this->dispatch();
    }

    /**
     *  Mark as failed the running tasks whose process has disappeared (killed process, crash, restart...), so
     *  that they do not hold a slot of the queue forever
     *  Return the Id of the tasks marked as failed
     */
    public function reconcile(): array
    {
        $orphans = $this->getOrphans();

        if (empty($orphans)) {
            return [];
        }

        /**
         *  A task being stopped or ending can briefly look like an orphan (its process is gone but its status
         *  is not updated yet), so orphans are confirmed a few seconds later
         */
        sleep(5);

        $aborted = [];

        foreach (array_intersect($orphans, $this->getOrphans()) as $taskId) {
            if ($this->taskController->abort($taskId, 'The task process is not running anymore, it may have crashed or been killed')) {
                $aborted[] = $taskId;
            }
        }

        return $aborted;
    }

    /**
     *  Close the running parent tasks whose sub-tasks have all ended, in case the last of them could not
     *  close it (e.g. its process was killed right after it ended, by a restart)
     *  A parent task with ongoing sub-tasks is left untouched, and a parent task is closed (and notified) once only.
     *  Return the Id of the parent tasks closed
     */
    public function closeParents(): array
    {
        $closed = [];

        foreach ($this->taskListingController->getRunning() as $task) {
            if (!$this->taskController->isParent((int) $task['Id'])) {
                continue;
            }

            $this->taskController->closeParent((int) $task['Id']);

            if (($this->taskController->getById((int) $task['Id'])['Status'] ?? '') != 'running') {
                $closed[] = (int) $task['Id'];
            }
        }

        return $closed;
    }

    /**
     *  Return the Id of the running tasks that have no process to run them
     *  Tasks having sub-tasks are not concerned, they never have a process and are closed by their sub-tasks
     */
    private function getOrphans(): array
    {
        $orphans = [];

        foreach ($this->taskListingController->getExecutable('running') as $task) {
            /**
             *  A task grouping sub-tasks has no process of its own, it is an orphan only if it never created its
             *  sub-tasks (they are created right after it is set as running)
             */
            if (self::isGrouping($task)) {
                if (!empty($task['Date']) and !empty($task['Time']) and time() - strtotime($task['Date'] . ' ' . $task['Time']) < self::GROUPING_GRACE) {
                    continue;
                }

                $orphans[] = (int) $task['Id'];
                continue;
            }

            if (!Task::processRunning((int) $task['Id'])) {
                $orphans[] = (int) $task['Id'];
            }
        }

        return $orphans;
    }

    /**
     *  Return the number of slots used by running tasks
     *  A task grouping sub-tasks uses no slot, only its sub-tasks do. It is only listed as running while it has
     *  no sub-task yet, i.e. while they are being created.
     */
    private function countRunning(): int
    {
        return count(array_filter($this->taskListingController->getExecutable('running'), function (array $task): bool {
            return !self::isGrouping($task);
        }));
    }

    /**
     *  Return true if the task groups sub-tasks (a parent task, or a scheduled task targeting a dynamic set of repositories)
     */
    private static function isGrouping(array $task): bool
    {
        $taskRawParams = json_decode($task['Raw_params'] ?? '', true);

        return is_array($taskRawParams) and (!empty($taskRawParams['tasks']) or Target::isDynamic($taskRawParams));
    }

    /**
     *  Return the maximum number of simultaneous tasks, or null if tasks are not queued (no limit)
     *  Settings are read from database, as they may have changed since the process started
     */
    private function getMaxSimultaneous(): int|null
    {
        $settings = $this->settingsController->get();

        if (($settings['TASK_QUEUING'] ?? 'false') != 'true') {
            return null;
        }

        // Same default value as the TASK_QUEUING_MAX_SIMULTANEOUS constant
        if (empty($settings['TASK_QUEUING_MAX_SIMULTANEOUS']) or !is_numeric($settings['TASK_QUEUING_MAX_SIMULTANEOUS'])) {
            return 2;
        }

        return max(1, (int) $settings['TASK_QUEUING_MAX_SIMULTANEOUS']);
    }

    /**
     *  Start the process that will claim and run the specified task, in background
     *  Output is appended, as several processes may be started for the same task (only one of them runs it)
     *  A process failing to start leaves its task queued, it is started again by the next dispatch.
     */
    private function spawn(int $taskId): void
    {
        $process = new Process('/usr/bin/php ' . ROOT . '/tasks/execute.php --id="' . $taskId . '" >> ' . MAIN_LOGS_DIR . '/repomanager-task-' . $taskId . '-log.process 2>&1 &');
        $process->execute();
        $process->close();
    }
}
