<?php

namespace Controllers\User;

use Exception;
use JsonException;

class Permission
{
    private $model;
    private $userController;

    public function __construct()
    {
        $this->model = new \Models\User\Permission();
        $this->userController = new \Controllers\User\User();
    }

    /**
     *  Get user permissions
     */
    public function get(int $id) : array
    {
        $permissions = $this->model->get($id);

        /**
         *  Decode permissions (JSON) and return them
         */
        try {
            $permissions = json_decode($permissions, true, 512, JSON_THROW_ON_ERROR);
            return $permissions;
        } catch (Exception $e) {
            throw new Exception('error decoding permissions: ' . $e->getMessage());
        }
    }

    /**
     *  Set user permissions
     */
    public function set(int $id, array $reposView, array $reposActions) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        $permissions = [
            'repositories' => [
                'view' => [
                    'groups' => [],
                ],
                'allowed-actions' => [
                    'repos' => [],
                    'hosts' => [],
                ],
            ],
        ];

        /**
         *  Check if user exists
         */
        if (!$this->userController->existsId($id)) {
            throw new Exception('User does not exist.');
        }

        /**
         *  Check that user is not an administrator
         */
        $role = $this->userController->getRoleById($id);

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
                $permissions['repositories']['allowed-actions']['repos'][] = $action;
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
        $this->model->set($id, $permissions);
    }

    /**
     *  Delete user permissions
     */
    public function delete(int $id) : void
    {
        $this->model->delete($id);
    }
}
