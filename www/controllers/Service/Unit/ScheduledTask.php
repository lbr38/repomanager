<?php

namespace Controllers\Service\Unit;

use Exception;
use DateTime;
use Controllers\Task\Target;

class ScheduledTask extends \Controllers\Service\Service
{
    private $taskController;
    private $taskListingController;
    private $taskNotifyController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->taskController = new \Controllers\Task\Task();
        $this->taskListingController = new \Controllers\Task\Listing();
        $this->taskNotifyController = new \Controllers\Task\Notify();
    }

    /**
     *  Execute scheduled tasks if any
     */
    public function execute() : void
    {
        parent::log('Executing scheduled tasks if any...');

        /**
         *  Quit if there was an error while loading general settings
         */
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            return;
        }

        $taskToExec = [];
        $tasksRawParams = [];
        $dateNow = date('Y-m-d');
        $timeNow = date('H:i');
        $minutesNow = date('i');
        $dayNow = strtolower(date('l')); // day of the week (e.g. monday)

        /**
         *  Get scheduled tasks
         */
        $scheduledTasks = $this->taskListingController->getScheduled();

        /**
         *  Quit if there is no task to execute
         */
        if (empty($scheduledTasks)) {
            return;
        }

        /**
         *  Loop through scheduled tasks
         */
        foreach ($scheduledTasks as $task) {
            /**
             *  Skip disabled tasks
             */
            if ($task['Status'] == 'disabled') {
                continue;
            }

            $taskRawParams = json_decode($task['Raw_params'], true);

            // Keep the parameters aside, they are needed when the task is actually executed
            $tasksRawParams[$task['Id']] = $taskRawParams;

            /**
             *  Case where the task is a unique task
             */
            if (!empty($taskRawParams['schedule']['schedule-date']) and !(empty($taskRawParams['schedule']['schedule-time']))) {
                /**
                 *  If the date and time correspond with the current date and time then add the task to the list of tasks to execute
                 */
                if ($taskRawParams['schedule']['schedule-date'] == $dateNow and $taskRawParams['schedule']['schedule-time'] == $timeNow) {
                    $taskToExec[] = $task['Id'];
                }
            }

            /**
             *  Case where the task is a recurring task
             */
            if (!empty($taskRawParams['schedule']['schedule-frequency'])) {
                /**
                 *  Case where the frequency is 'hourly' and the current time is xx:00
                 */
                if ($taskRawParams['schedule']['schedule-frequency'] == 'hourly' and $minutesNow == '00') {
                    $taskToExec[] = $task['Id'];
                }

                /**
                 *  Case where the frequency is 'daily' and the current time is the same as the task scheduled time
                 */
                if ($taskRawParams['schedule']['schedule-frequency'] == 'daily' and $taskRawParams['schedule']['schedule-time'] == $timeNow) {
                    $taskToExec[] = $task['Id'];
                }

                /**
                 *  Case where the frequency is 'weekly'
                 */
                if ($taskRawParams['schedule']['schedule-frequency'] == 'weekly' and !empty($taskRawParams['schedule']['schedule-day']) and !empty($taskRawParams['schedule']['schedule-time'])) {
                    /**
                     *  Loop through the list of days specified by the user
                     */
                    foreach ($taskRawParams['schedule']['schedule-day'] as $day) {
                        /**
                         *  If the day and the time correspond with the current day and time then add the task to the list of tasks to execute
                         */
                        if ($day == $dayNow and $taskRawParams['schedule']['schedule-time'] == $timeNow) {
                            $taskToExec[] = $task['Id'];
                        }
                    }
                }

                /**
                 *  Case where the frequency is 'monthly'
                 */
                if ($taskRawParams['schedule']['schedule-frequency'] == 'monthly' and !empty($taskRawParams['schedule']['schedule-monthly-day-position']) and !empty($taskRawParams['schedule']['schedule-monthly-day']) and !empty($taskRawParams['schedule']['schedule-time'])) {
                    /**
                     *  Determine day position
                     *  e.g. 1st monday of the month, last friday of the month, ...
                     */

                    /**
                     *  First, define a DateTime object with the current date or whatever
                     *  Then modify the date to get the first/second/third/last monday/tuesday/... of the month and retrieve the date
                     */
                    $dateObject = new DateTime(DATE_YMD);
                    $taskDate = $dateObject->modify($taskRawParams['schedule']['schedule-monthly-day-position'] . ' ' . $taskRawParams['schedule']['schedule-monthly-day'] . ' of this month')->format('Y-m-d');

                    /**
                     *  If the date and time correspond with the current date and time then add the task to the list of tasks to execute
                     */
                    if ($taskDate == $dateNow and $taskRawParams['schedule']['schedule-time'] == $timeNow) {
                        $taskToExec[] = $task['Id'];
                    }

                    unset($dateObject, $taskDate);
                }

                /**
                 *  Case where the frequency is 'cron'
                 */
                if ($taskRawParams['schedule']['schedule-frequency'] == 'cron' and !empty($taskRawParams['schedule']['schedule-cron'])) {
                    try {
                        if (\Controllers\Utils\Cron::matches($taskRawParams['schedule']['schedule-cron'], new DateTime(date('Y-m-d H:i')))) {
                            $taskToExec[] = $task['Id'];
                        }
                    } catch (Exception $e) {
                        parent::log('Invalid cron expression for task #' . $task['Id'] . ': ' . $e->getMessage());
                    }
                }
            }
        }

        /**
         *  Execute scheduled tasks
         */
        if (!empty($taskToExec)) {
            foreach ($taskToExec as $taskId) {
                parent::log('Launching scheduled task #' . $taskId . '...');

                try {
                    $this->dispatch($taskId, $tasksRawParams[$taskId] ?? []);
                } catch (Exception $e) {
                    throw new Exception('Error while executing scheduled task #' . $taskId . ': ' . $e->getMessage());
                }

                // Let some time between each task, to make sure the queue system works properly
                sleep(1);
            }
        }
    }

    /**
     *  Dispatch a scheduled task that has reached its execution time
     *  A scheduled task is never executed as is: it becomes the parent task of the sub-tasks it
     *  launches, one per targeted repository, and its status summarizes theirs
     */
    private function dispatch(int $taskId, array $taskRawParams): void
    {
        // Keep the schedule type aside, as the sub-tasks parameters do not carry the schedule
        $scheduleType = $taskRawParams['schedule']['schedule-type'] ?? '';
        $tasksParams = [];
        $grouped = true;

        /**
         *  Retrieve the parameters of the sub-tasks to launch
         *  A dynamic target is resolved into one sub-task per matching repository, while a task
         *  targeting several repositories already holds the parameters of each of them
         */
        if (Target::isDynamic($taskRawParams)) {
            $targetController = new Target();
            $tasksParams = $targetController->expand($taskRawParams);
        } elseif (!empty($taskRawParams['tasks'])) {
            $tasksParams = $taskRawParams['tasks'];
        } else {
            // A task targeting a single repository has no sub-task, it is executed as is
            $grouped = false;
        }

        // The sub-tasks run immediately, the schedule is carried by the parent task only
        foreach ($tasksParams as $key => $subTaskParams) {
            $tasksParams[$key]['schedule'] = ['scheduled' => 'false'];
        }

        /**
         *  A recurring task must run again at its next occurrence, so a copy of it is scheduled
         *  before the current one is consumed by this execution
         */
        if ($scheduleType == 'recurring') {
            $nextTaskId = $this->taskController->duplicate($taskId);

            // The copy has not run yet, so it has no execution date and time
            $this->taskController->updateDate($nextTaskId, '');
            $this->taskController->updateTime($nextTaskId, '');
            $this->taskController->updateStatus($nextTaskId, 'scheduled');
        }

        // Keep track of the moment the task actually ran, its sub-tasks duration is counted from there
        $this->taskController->updateDate($taskId, date('Y-m-d'));
        $this->taskController->updateTime($taskId, date('H:i:s'));

        // A task without sub-task is queued and executed on its own
        if (!$grouped) {
            $this->taskController->updateStatus($taskId, 'queued');
            $this->taskController->executeId($taskId);

            return;
        }

        /**
         *  A dynamic target may match no repository at all, in which case there is nothing to run
         *  and the task is closed immediately
         */
        if (empty($tasksParams)) {
            parent::log('No repository matches the target of scheduled task #' . $taskId);
            $this->taskController->updateStatus($taskId, 'done');

            return;
        }

        parent::log('Scheduled task #' . $taskId . ' launches ' . count($tasksParams) . ' sub-task(s)');

        /**
         *  The task is marked as running before its sub-tasks are launched, as the last sub-task to
         *  end is the one that closes it
         */
        $this->taskController->updateStatus($taskId, 'running');

        $this->taskController->execute($tasksParams, $taskId);
    }

    /**
     *  Send scheduled tasks reminders
     */
    public function sendReminders() : void
    {
        try {
            /**
             *  Quit if current time != 00:00
             */
            if (date('H:i') != '00:00') {
                return;
            }

            parent::log('Sending scheduled tasks reminder if any...');

            $tasksToReminder = [];
            $dateNow = date('Y-m-d');

            /**
             *  Get scheduled tasks
             */
            $scheduledTasks = $this->taskListingController->getScheduled();

            /**
             *  Quit if there is no task to execute
             */
            if (empty($scheduledTasks)) {
                return;
            }

            /**
             *  Loop through scheduled tasks
             *  Reverse the array to get the latest tasks first
             */
            foreach (array_reverse($scheduledTasks) as $task) {
                /**
                 *  Skip disabled tasks
                 */
                if ($task['Status'] == 'disabled') {
                    continue;
                }

                $taskRawParams = json_decode($task['Raw_params'], true);

                /**
                 *  If the task has no mail recipient then skip it
                 */
                if (empty($taskRawParams['schedule']['schedule-recipient'])) {
                    continue;
                }

                /**
                 *  If the task is a unique task
                 */
                if ($taskRawParams['schedule']['schedule-type'] == 'unique') {
                    /**
                     *  A scheduled task can have 1 or more reminders.
                     *  For each reminder, check if its date corresponds to the current date less (-) the number of days of the reminder
                     */
                    foreach ($taskRawParams['schedule']['schedule-reminder'] as $reminder) {
                        $reminderDate = date_create($taskRawParams['schedule']['schedule-date'])->modify('-' . $reminder . 'days')->format('Y-m-d');

                        if ($reminderDate == $dateNow) {
                            /**
                             *  Task Id is added to the array of tasks to remind
                             */
                            $tasksToReminder[] = $task['Id'];
                        }
                    }
                }
            }

            // Quit if there is no task to remind
            if (empty($tasksToReminder)) {
                return;
            }

            // Send reminders
            $this->taskNotifyController->reminder($tasksToReminder);
        } catch (Exception $e) {
            parent::log('Error while sending scheduled tasks reminders: ' . $e->getMessage());
        }
    }
}
