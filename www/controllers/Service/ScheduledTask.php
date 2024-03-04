<?php

namespace Controllers\Service;

use Exception;
use Datetime;

class ScheduledTask extends Service
{
    protected $logController;
    private $taskController;

    public function __construct()
    {
        $this->logController = new \Controllers\Log\Log();
        $this->taskController = new \Controllers\Task\Task();
    }

    /**
     *  Execute scheduled tasks if any
     */
    public function execute()
    {
        echo $this->getDate() . ' Executing scheduled tasks if any...' . PHP_EOL;

        /**
         *  Quit if there was an error while loading general settings
         */
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            $this->logController->log('error', 'Service', 'Cannot execute scheduled task: error while loading general settings');
            return;
        }

        $taskToExec = array();
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

            /**
             *  Case where the task is a unique task
             */
            if (!empty($task['Schedule_date']) and !(empty($task['Schedule_time']))) {
                /**
                 *  If the date and time correspond with the current date and time then add the task to the list of tasks to execute
                 */
                if ($task['Schedule_date'] == $dateNow and $task['Schedule_time'] == $timeNow) {
                    $taskToExec[] = $task['Id'];
                }
            }

            /**
             *  Case where the task is a recurring task
             */
            if (!empty($task['Schedule_frequency'])) {
                /**
                 *  Case where the frequency is 'hourly' and the current time is xx:00
                 */
                if ($task['Schedule_frequency'] == 'hourly' and $minutesNow == '00') {
                    $taskToExec[] = $task['Id'];
                }

                /**
                 *  Case where the frequency is 'daily' and the current time is the same as the task scheduled time
                 */
                if ($task['Schedule_frequency'] == 'daily' and $task['Schedule_time'] = $timeNow) {
                    $taskToExec[] = $task['Id'];
                }

                /**
                 *  Case where the frequency is 'weekly'
                 */
                if ($task['Schedule_frequency'] == 'weekly' and !empty($task['Schedule_day']) and !empty($task['Schedule_time'])) {
                    /**
                     *  Loop through the list of days specified by the user
                     */
                    $scheduleDay = explode(',', $task['Schedule_day']);

                    foreach ($scheduleDay as $day) {
                        /**
                         *  If the day and the time correspond with the current day and time then add the task to the list of tasks to execute
                         */
                        if ($day == $dayNow and $task['Schedule_time'] == $timeNow) {
                            $taskToExec[] = $task['Id'];
                        }
                    }
                }
            }
        }

        /**
         *  Execute scheduled tasks
         */
        if (!empty($taskToExec)) {
            foreach ($taskToExec as $taskId) {
                echo $this->getDate() . ' Launching scheduled task #' . $taskId . '...' . PHP_EOL;

                try {
                    $this->taskController->executeId($taskId);
                } catch (Exception $e) {
                    $this->logController->log('error', 'Service', 'Error while launching scheduled task: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     *  Send scheduled tasks reminders
     */
    public function sendReminders()
    {
        /**
         *  Exit the function if current time != 00:00
         *  TODO : désactivé pour test, à réactiver
         */
        // if (date('H:i') != '00:00') {
        //     return;
        // }

        echo $this->getDate() . ' Sending scheduled tasks reminder if any...' . PHP_EOL;

        /**
         *  Quit if there was an error while loading general settings
         */
        if (defined('__LOAD_GENERAL_ERROR') and __LOAD_GENERAL_ERROR > 0) {
            $this->logController->log('error', 'Service', 'Cannot execute scheduled task: error while loading general settings');
            return;
        }

        $taskToReminder = array();
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
         */
        foreach ($scheduledTasks as $task) {
            /**
             *  Skip disabled tasks
             */
            if ($task['Status'] == 'disabled') {
                continue;
            }

            /**
             *  If the task is a unique task
             */
            if (!empty($task['Schedule_date']) and !(empty($task['Schedule_time']))) {
                /**
                 *  A scheduled task can have 1 or more reminders.
                 *  For each reminder, check if its date corresponds to the current date less (-) the number of days of the reminder
                 */
                foreach (explode(',', $task['Schedule_reminder']) as $reminder) {
                    $reminderDate = date_create($task['Schedule_date'])->modify("-$reminder days")->format('Y-m-d');

                    if ($reminderDate == $dateNow) {
                        /**
                         *  Task Id is added to the array of tasks to remind
                         */
                        $taskToReminder[] = $task['Id'];
                    }
                }
            }
        }

        /**
         *  Send reminders
         */

        /**
         *  Quit if there is no task to remind
         */
        if (empty($taskToReminder)) {
            return;
        }

        try {
            $reminderMessage .= '<span><b>Scheduled tasks of the ' . DateTime::createFromFormat('Y-m-d', $task['Schedule_date'])->format('d-m-Y') . ':</b></span><br>';

            foreach ($taskToReminder as $taskId) {
                /**
                 *  Get task details
                 */
                $task = $this->taskController->getById($taskId);

                /**
                 *  Generate reminder message
                 */
                $msg = $this->taskController->generateReminders($taskId);
                $reminderMessage .= '<span>' . $msg . '</span><br><hr>';
            }

            if (empty($reminderMessage)) {
                return;
            }

            /**
             *  Send email
             */
            $mailSubject = '[ Reminder ] Scheduled task(s) to come on ' . WWW_HOSTNAME;
            $mymail = new \Controllers\Mail($this->taskController->getMailRecipient(), $mailSubject, $reminderMessage, 'https://' . WWW_HOSTNAME . '/plans', 'Scheduled tasks');
        } catch (Exception $e) {
            $this->logController->log('error', 'Service', 'Error while sending scheduled tasks reminder: ' . $e->getMessage());
        }
    }
}
