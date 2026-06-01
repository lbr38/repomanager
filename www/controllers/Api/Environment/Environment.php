<?php

namespace Controllers\Api\Environment;

use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Repo\Snapshot\Snapshot;
use Controllers\Environment as EnvController;
use Controllers\Utils\Validate;
use Controllers\Task\Task;
use Exception;

class Environment extends \Controllers\Api\Controller
{
    public function execute(): array
    {
        if ($this->method == 'PATCH') {
            /**
             *  Point an environment to a snapshot
             *  https://repomanager.mydomain.net/api/v2/env/prod/point?snapshot=1
             */
            if (!empty($this->uri[5]) and $this->uri[5] == 'point') {
                $repoSnapshotController = new Snapshot();
                $envController = new EnvController();

                // Check that the user has permission to point the environment
                if (!RepoPermission::allowedAction('env')) {
                    throw new Exception('You are not allowed to point an environment to a snapshot');
                }

                // Check if the snapshot parameter is provided
                if (empty($this->data['snapshot'])) {
                    throw new Exception('Snapshot Id is required');
                }

                // Retrieve the environment from the URI
                $env = Validate::string($this->uri[4]);

                // Retrieve the snapshot Id from the query parameters
                $snapId = $this->data['snapshot'];

                // Check if the environment exists
                if (!$envController->exists($env)) {
                    throw new Exception('Environment ' . $env . ' does not exist');
                }

                // Check if the snapshot exists
                if (!$repoSnapshotController->exists($snapId)) {
                    throw new Exception('Snapshot #' . $snapId . ' does not exist');
                }

                // Create a json file that defines the task to execute
                $params = [];
                $params['action'] = 'env';
                $params['snap-id'] = $snapId;
                $params['env'] = ['prod'];
                $params['description'] = '';
                $params['schedule']['scheduled'] = 'false';

                // Execute the task
                $taskController = new Task();
                $taskId = $taskController->execute([$params]);

                return [
                    'rc' => 202,
                    'results' => 'Environment point started',
                    'task-id' => $taskId
                ];
            }
        }

        throw new Exception('Invalid request');
    }
}
