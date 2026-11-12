<?php

namespace Controllers\Api\Environment;

use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Repo\Snapshot\Snapshot;
use Controllers\Utils\Validate;
use Exception;

class Environment extends \Controllers\Api\Controller
{
    public function execute(): array
    {
        if ($this->method == 'PATCH') {
            /**
             *  Point an environment to a snapshot
             *  https://repomanager.mydomain.net/api/v2/environment/{env}/point
             */
            if (!empty($this->uri[5]) and $this->uri[5] == 'point') {
                $repoSnapshotController = new Snapshot();

                // Check that the user has permission to point the environment
                if (!RepoPermission::allowedAction('env')) {
                    throw new Exception('You are not allowed to point an environment to a snapshot');
                }

                // Check if the snapshot parameter is provided
                if (empty($this->data['snapshot']) or !is_numeric($this->data['snapshot'])) {
                    throw new Exception('Snapshot Id is required');
                }

                // Retrieve the environment from the URI and the snapshot Id from the JSON data
                $env = Validate::string($this->uri[4]);
                $snapId = (int) $this->data['snapshot'];

                // Check if the snapshot exists and if the user is allowed to view its repository
                if (!$repoSnapshotController->exists($snapId)) {
                    throw new Exception('Snapshot #' . $snapId . ' does not exist');
                }

                if (!RepoPermission::allowedToView((int) $repoSnapshotController->getById($snapId)['Id_repo'])) {
                    throw new Exception('You are not allowed to access snapshot #' . $snapId);
                }

                // Same parameters and validation as the environment form (permission, existing and unprotected environment...)
                $params = [];
                $params['action'] = 'env';
                $params['snap-id'] = strval($snapId);
                $params['env'] = [$env];
                $params['description'] = '';
                $params['schedule']['scheduled'] = 'false';

                $taskId = (new \Controllers\Task\Launcher())->launch([$params]);

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
