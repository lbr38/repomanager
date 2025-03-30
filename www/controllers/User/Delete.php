<?php
namespace Controllers\User;

use Exception;

class Delete extends User
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new \Models\User\Delete();
    }

    /**
     *  Delete user
     */
    public function delete(string $id) : void
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        /**
         *  Get username
         */
        $username = $this->getUsernameById($id);

        /**
         *  Get role
         */
        $role = $this->getRoleById($id);

        /**
         *  Super-administrator cannot be deleted
         */
        if ($role == 1) {
            throw new Exception('You are not allowed to delete a super-administrator');
        }

        /**
         *  If the current user is not a superadmin (he's only an admin), then he cannot delete another admin
         */
        // if (!IS_SUPERADMIN) {
        //     if ($role == 2) {
        //         throw new Exception('You are not allowed to delete another administrator');
        //     }
        // }

        if (empty($username)) {
            throw new Exception('Specified user does not exist');
        }

        /**
         *  Delete user
         */
        $this->model->delete($id);

        $this->historyController->set('Delete user ' . $username, 'success');
    }
}
