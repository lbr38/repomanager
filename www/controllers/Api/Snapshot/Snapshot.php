<?php

namespace Controllers\Api\Snapshot;

use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Repo\Snapshot\Package;
use Controllers\Utils\Convert;
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
        if (!empty($this->uri[4])) {
            $this->snapId = $this->uri[4];
        }

        // Retrieve uploaded FILES if any
        if (!empty($_FILES)) {
            $this->postFiles = $_FILES;
        }

        $repoSnapshotPackageController = new Package($this->snapId);

        if ($this->method == 'GET') {
            /**
             *  List snapshot details
             *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/
             */
            if (empty($this->uri[5])) {
                return ['results' => $repoSnapshotController->getById($this->snapId)];
            }

            /**
             *  List all packages of a snapshot
             *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/packages
             */
            if ($this->uri[5] == 'packages') {
                return ['results' => $repoSnapshotPackageController->list()];
            }
        }

        if ($this->method == 'POST') {
            /**
             *  Upload packages to a snapshot
             *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/upload
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
             *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/rebuild
             */
            if ($this->uri[5] == 'rebuild' and !empty($this->data['gpgSign'])) {
                /**
                 *  Same code as controllers/ajax/browse.php
                 *  TODO : find a way to not duplicate code
                 */
                $mytask = new \Controllers\Task\Task();

                if (!RepoPermission::allowedAction('rebuild')) {
                    throw new Exception('You are not allowed to rebuild a repository snapshot');
                }

                if ($this->data['gpgSign'] != 'true' and $this->data['gpgSign'] != 'false') {
                    throw new Exception('Invalid GPG sign value');
                }

                // If a task is already running on the snapshot, throw an error
                if ($repoSnapshotController->taskRunning($this->snapId)) {
                    throw new Exception('A task is already running on this repository snapshot. Retry later.');
                }

                // Create a json file that defines the task to execute
                $params = [];
                $params['action'] = 'rebuild';
                $params['snap-id'] = $this->snapId;
                $params['gpg-sign'] = $this->data['gpgSign'];
                $params['schedule']['scheduled'] = 'false';

                // Execute the task
                $mytask->execute([$params]);

                unset($mytask);

                return [
                    'rc' => 202,
                    'results' => 'Snapshot metadata rebuild started'
                ];
            }
        }

        if ($this->method == 'DELETE') {
            /**
             *  Delete packages from a snapshot
             *  https://repomanager.mydomain.net/api/v2/snapshot/$this->snapId/packages
             */
            if ($this->uri[5] == 'packages' and !empty($this->data['packages'])) {
                return ['results' => ['Packages deleted successfully:' => $repoSnapshotPackageController->deleteByName($this->data['packages'])]];
            }
        }

        throw new Exception('Invalid request');
    }
}
