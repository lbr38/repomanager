<?php

namespace Controllers\User;

use Exception;
use JsonException;

class Permission extends User
{
    private $permissionModel;
    private $defaultPermissions = [
        'repositories' => [
            'allowed-actions' => [],
            'view' => [
                'all',
                'groups' => []
            ],
        ],
        'tasks' => [
            'allowed-actions' => [],
        ],
        'hosts' => [
            'allowed-actions' => [],
        ],
    ];

    public function __construct()
    {
        parent::__construct();
        $this->permissionModel = new \Models\User\Permission();
    }

    /**
     *  Get user permissions
     */
    public function get(int $id) : array
    {
        $permissions = $this->permissionModel->get($id);

        /**
         *  If permissions are empty, create default permissions and return them
         */
        if (empty($permissions)) {
            try {
                $this->permissionModel->set($id, json_encode($this->defaultPermissions, JSON_THROW_ON_ERROR));
            } catch (JsonException $e) {
                throw new Exception('Error setting default permissions: ' . $e->getMessage());
            }

            return $this->defaultPermissions;
        }

        /**
         *  Decode permissions (JSON) and return them
         */
        try {
            $permissions = json_decode($permissions, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            throw new Exception('error decoding permissions: ' . $e->getMessage());
        }

        /**
         *  Merge with default permissions if some keys are missing
         */
        $permissions = array_merge_recursive($this->defaultPermissions, $permissions);

        return $permissions;
    }

    /**
     *  Get default permissions definition
     */
    public function getDefault() : array
    {
        return $this->defaultPermissions;
    }

    /**
     *  Set user permissions
     */
    public function set(int $id, array $reposView, array $reposActions, array $tasksActions, array $hostsActions) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        $permissions = $this->defaultPermissions;

        /**
         *  Check if user exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('User does not exist.');
        }

        /**
         *  Check that user is not an administrator
         */
        $role = $this->getRoleById($id);

        if ($role == '1' || $role == '2') {
            throw new Exception('You are not allowed to set permissions for an administrator or super-administrator.');
        }

        /**
         *  Set permissions for repositories view
         */
        foreach (array_filter($reposView) as $repoOrGroup) {
            /**
             *  If 'all' is selected, then set view permission to 'all' and ignore other entries
             */
            if (in_array('all', $reposView)) {
                $permissions['repositories']['view'][] = 'all';
                break;
            }

            /**
             *  If entry starts with 'group-', then it's a group
             */
            if (strpos($repoOrGroup, 'group-') === 0) {
                // Get group ID
                $groupId = str_replace('group-', '', $repoOrGroup);

                // Check that group ID is valid
                if (!is_numeric($groupId)) {
                    throw new Exception('Invalid group Id: ' . $groupId);
                }

                // Add group Id key to permissions
                $permissions['repositories']['view']['groups'][] = $groupId;
            }
        }

        /**
         *  Set permissions for repositories actions
         */
        if (!empty($reposActions)) {
            foreach (array_filter($reposActions) as $action) {
                // Check that action is valid
                if (!in_array($action, ['create', 'update', 'duplicate', 'rebuild', 'edit', 'delete', 'env', 'removeEnv', 'browse', 'upload-package', 'delete-package', 'view-stats'])) {
                    throw new Exception('Invalid action: ' . $action);
                }

                // Add action to allowed actions
                $permissions['repositories']['allowed-actions'][] = $action;
            }
        }

        /**
         *  Set permissions for tasks actions
         */
        if (!empty($tasksActions)) {
            foreach (array_filter($tasksActions) as $action) {
                // Check that action is valid
                if (!in_array($action, ['relaunch', 'delete', 'enable', 'disable', 'stop'])) {
                    throw new Exception('Invalid action: ' . $action);
                }

                // Add action to allowed actions
                $permissions['tasks']['allowed-actions'][] = $action;
            }
        }

        /**
         *  Set permissions for hosts actions
         */
        if (!empty($hostsActions)) {
            foreach (array_filter($hostsActions) as $action) {
                // Check that action is valid
                if (!in_array($action, ['request-general-infos', 'request-packages-infos', 'update-packages', 'reset', 'delete'])) {
                    throw new Exception('Invalid action: ' . $action);
                }

                // Add action to allowed actions
                $permissions['hosts']['allowed-actions'][] = $action;
            }
        }

        /**
         *  Encode permissions to JSON
         */
        try {
            $permissions = json_encode($permissions, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Error encoding permissions: ' . $e->getMessage());
        }

        /**
         *  Set permissions in database
         */
        $this->permissionModel->set($id, $permissions);
    }

    /**
     *  Delete user permissions
     */
    public function delete(int $id) : void
    {
        $this->permissionModel->delete($id);
    }
}
