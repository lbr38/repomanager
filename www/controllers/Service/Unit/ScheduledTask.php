<?php

namespace Controllers\Service\Unit;

use Exception;
use DateTime;

class ScheduledTask extends \Controllers\Service\Service
{
    private $taskController;
    private $taskNotifyController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->taskController = new \Controllers\Task\Task();
        $this->taskNotifyController = new \Controllers\Task\Notify();
    }

    /**
     *  Execute scheduled tasks if any
     */
    public function execute()
    {
        parent::log('Executing scheduled tasks if any...');

        /**
         *  Quit if there was an error while loading general settings
         */
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            return;
        }

        $taskToExec = [];
        $dateNow = date('Y-m-d');
        $timeNow = date('H:i');
        $minutesNow = date('i');
        $dayNow = strtolower(date('l')); // day of the week (e.g. monday)

        /**
         *  Get scheduled tasks
         */
        $scheduledTasks = $this->taskController->listScheduled();

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
            }
        }

        /**
         *  Execute scheduled tasks
         */
        if (!empty($taskToExec)) {
            foreach ($taskToExec as $taskId) {
                parent::log('Launching scheduled task #' . $taskId . '...');

                try {
                    // Add the scheduled task to the queue and execute it
                    $this->taskController->updateStatus($taskId, 'queued');
                    $this->taskController->executeId($taskId);
                } catch (Exception $e) {
                    throw new Exception('Error while executing scheduled task #' . $taskId . ': ' . $e->getMessage());
                }

                // Let some time between each task, to make sure the queue system works properly
                sleep(1);
            }
        }
    }

    /**
     *  Send scheduled tasks reminders
     */
    public function sendReminders()
    {
        /**
         *  Quit if current time != 00:00
         */
        if (date('H:i') != '00:00') {
            return;
        }

        parent::log('Sending scheduled tasks reminder if any...');

        /**
         *  Quit if there was an error while loading general settings
         */
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            return;
        }

        $tasksToReminder = [];
        $dateNow = date('Y-m-d');
        $reminderMessage = '';

        /**
         *  Get scheduled tasks
         */
        $scheduledTasks = $this->taskController->listScheduled();

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
                    $reminderDate = date_create($taskRawParams['schedule']['schedule-date'])->modify("-$reminder days")->format('Y-m-d');

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
    }
}
