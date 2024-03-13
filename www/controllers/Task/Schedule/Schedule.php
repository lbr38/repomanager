<?php

namespace Controllers\Task\Schedule;

use Exception;

class Schedule
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Task\Schedule\Schedule();
    }

    public function getScheduled()
    {
        return $this->model->getScheduled();
    }

    public function execute()
    {
        $dateNow = date('Y-m-d');
        $timeNow = date('H:i');
        $toExecute[] = array();

        /**
         *  Get scheduled tasks
         */
        $scheduled = $this->getScheduled();

        /**
         *  Quit if no tasks are scheduled
         */
        if (empty($scheduled)) {
            return;
        }

        /**
         *  Check if the task is due to be executed
         */
        foreach ($scheduled as $task) {
            /**
             *  If task date and time match current date and time, add to the list of tasks to execute
             */
            if ($task['Date'] == $dateNow && $task['Time'] == $timeNow) {
                $toExecute[] = $task['Id'];
            }
        }

        /**
         *  Quit if no tasks are due to be executed
         */
        if (empty($toExecute)) {
            return;
        }

        /**
         *  Execute the tasks
         */
        foreach ($toExecute as $taskId) {
            echo 'task Id ' . $taskId . ' is due to be executed';
            // $myprocess = new \Controllers\Process('/usr/bin/php ' . ROOT . '/tasks/execute.php --id="' . $taskId . '" >/dev/null 2>/dev/null &');
            // $myprocess->execute();
            // $myprocess->close();
        }
    }
}
