<?php

namespace Controllers\Api\Snapshot;

use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Repo\Snapshot\Package;
use Controllers\Utils\Convert;
use Controllers\Task\Task;
use Exception;

class Snapshot extends \Controllers\Api\Controller
{
    private $snapId;
    private $postFiles;

    public function execute(): array
    {
        $repoSnapshotController = new \Controllers\Repo\Snapshot\Snapshot();

        if (empty($this->uri[4])) {
            throw new Exception('No snapshot ID specified');
        }

        if (!is_numeric($this->uri[4])) {
            throw new Exception('Invalid snapshot ID');
        }

        if (!$repoSnapshotController->exists($this->uri[4])) {
            throw new Exception('Unknown snapshot ID');
        }

        // Retrieve snapshot Id
        $this->snapId = (int) $this->uri[4];

        // Whether the user is allowed to view the repository of the snapshot (packages upload and delete check it themselves)
        $allowedToView = RepoPermission::allowedToView((int) $repoSnapshotController->getById($this->snapId)['Id_repo']);

        // Retrieve uploaded FILES if any
        if (!empty($_FILES)) {
            $this->postFiles = $_FILES;
        }

        $repoSnapshotPackageController = new Package($this->snapId);

        if ($this->method == 'GET') {
            if (!$allowedToView) {
                throw new Exception('You are not allowed to access this snapshot');
            }

            /**
             *  List snapshot details
             *  https://repomanager.mydomain.net/api/v2/snapshot/{snap-id}/
             */
            if (empty($this->uri[5])) {
                return ['results' => $repoSnapshotController->getById($this->snapId)];
            }

            /**
             *  List all packages of a snapshot
             *  https://repomanager.mydomain.net/api/v2/snapshot/{snap-id}/packages
             */
            if ($this->uri[5] == 'packages') {
                return ['results' => $repoSnapshotPackageController->list()];
            }

            /**
             *  Make a diff between two snapshots
             *  https://repomanager.mydomain.net/api/v2/snapshot/{snap-id}/diff/{snap2-id}
             */
            if ($this->uri[5] == 'diff') {
                // Check if second snapshot ID is provided in query parameters and is valid
                if (empty($this->uri[6])) {
                    throw new Exception('No second snapshot Id specified for diff');
                }

                if (!is_numeric($this->uri[6])) {
                    throw new Exception('Invalid snapshot Id #' . $this->uri[6]);
                }

                if (!$repoSnapshotController->exists((int) $this->uri[6])) {
                    throw new Exception('Unknown snapshot ID #' . $this->uri[6]);
                }

                if (!RepoPermission::allowedToView((int) $repoSnapshotController->getById((int) $this->uri[6])['Id_repo'])) {
                    throw new Exception('You are not allowed to access snapshot #' . $this->uri[6]);
                }

                return ['results' => $repoSnapshotController->diff($this->snapId, $this->uri[6])];
            }
        }

        if ($this->method == 'POST') {
            /**
             *  Upload packages to a snapshot
             *  https://repomanager.mydomain.net/api/v2/snapshot/{snap-id}/upload
             */
            if ($this->uri[5] == 'upload' and !empty($this->postFiles)) {
                $overwrite = false;
                $ignoreIfExists = false;

                // If a null value is returned, it means that the value provided by the user is not a valid boolean string
                if (isset($_POST['overwrite']) && is_null($overwrite = Convert::toBool($_POST['overwrite']))) {
                    throw new Exception('Invalid overwrite value');
                }

                // If a null value is returned, it means that the value provided by the user is not a valid boolean string
                if (isset($_POST['ignore-if-exists']) && is_null($ignoreIfExists = Convert::toBool($_POST['ignore-if-exists']))) {
                    throw new Exception('Invalid ignore-if-exists value');
                }

                // Try to upload packages
                $return = $repoSnapshotPackageController->upload($this->postFiles, $overwrite, $ignoreIfExists);

                return ['results' => $return];
            }
        }

        if ($this->method == 'PUT') {
            /**
             *  Rebuild a snapshot
             *  https://repomanager.mydomain.net/api/v2/snapshot/{snap-id}/rebuild
             */
            if ($this->uri[5] == 'rebuild' and isset($this->data['gpgSign'])) {
                $taskController = new Task();

                if (!RepoPermission::allowedAction('rebuild')) {
                    throw new Exception('You are not allowed to rebuild a repository snapshot');
                }

                if (!$allowedToView) {
                    throw new Exception('You are not allowed to access this snapshot');
                }

                // If a task is already running on the snapshot, throw an error
                if ($repoSnapshotController->taskRunning($this->snapId)) {
                    throw new Exception('A task is already running on this repository snapshot. Retry later.');
                }

                // Same parameters and validation as the rebuild form (permission, protected environments...)
                $params = [];
                $params['action'] = 'rebuild';
                $params['snap-id'] = strval($this->snapId);
                // GPG signature can be specified as a boolean or as a 'true' / 'false' string
                $params['gpg-sign'] = is_bool($this->data['gpgSign']) ? ($this->data['gpgSign'] ? 'true' : 'false') : strval($this->data['gpgSign']);
                $params['schedule']['scheduled'] = 'false';

                (new \Controllers\Task\Form\Form())->validate([$params]);

                // Execute the task
                $taskId = $taskController->execute([$params]);

                return [
                    'rc' => 202,
                    'results' => 'Snapshot metadata rebuild started',
                    'task-id' => $taskId
                ];
            }
        }

        if ($this->method == 'DELETE') {
            /**
             *  Delete packages from a snapshot
             *  https://repomanager.mydomain.net/api/v2/snapshot/{snap-id}/packages
             */
            if ($this->uri[5] == 'packages' and !empty($this->data['packages'])) {
                return ['results' => ['Packages deleted successfully:' => $repoSnapshotPackageController->deleteByName($this->data['packages'])]];
            }
        }

        throw new Exception('Invalid request');
    }
}
