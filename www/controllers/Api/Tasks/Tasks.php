<?php

namespace Controllers\Api\Tasks;

use Controllers\Task\Listing as TaskListing;
use Exception;

class Tasks extends \Controllers\Api\Controller
{
    public function execute(): array
    {
        $taskListingController = new TaskListing();

        /**
         *  List all tasks
         *  https://repomanager.mydomain.net/api/v2/tasks/
         */
        if (empty($this->uri[4])) {
            if ($this->method == 'GET') {
                return ['results' => $taskListingController->get()];
            }
        }

        if (!empty($this->uri[4])) {
            /**
             *  List all running tasks
             *  https://repomanager.mydomain.net/api/v2/tasks/running
             */
            if ($this->uri[4] == 'running' and $this->method == 'GET') {
                return ['results' => $taskListingController->getRunning()];
            }

            /**
             *  List all queued tasks
             *  https://repomanager.mydomain.net/api/v2/tasks/queued
             */
            if ($this->uri[4] == 'queued' and $this->method == 'GET') {
                return ['results' => $taskListingController->getQueued()];
            }

            /**
             *  List all scheduled tasks
             *  https://repomanager.mydomain.net/api/v2/tasks/scheduled
             */
            if ($this->uri[4] == 'scheduled' and $this->method == 'GET') {
                return ['results' => $taskListingController->getScheduled()];
            }

            /**
             *  List all done tasks
             *  https://repomanager.mydomain.net/api/v2/tasks/done
             */
            if ($this->uri[4] == 'done' and $this->method == 'GET') {
                return ['results' => $taskListingController->getDone()];
            }
        }

        throw new Exception('Invalid request');
    }
}
