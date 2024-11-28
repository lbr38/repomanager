<?php

namespace Controllers;

use Exception;

class History
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\History();
    }

    /**
     *  Retrieve all history
     *  It is possible to add an offset to the request
     */
    public function getAll(bool $withOffset = false, int $offset = 0)
    {
        return $this->model->getAll($withOffset, $offset);
    }

    /**
     *  Retrieve all history from a user
     *  It is possible to add an offset to the request
     */
    public function getByUserId(int $id, bool $withOffset = false, int $offset = 0)
    {
        return $this->model->getByUserId($id, $withOffset, $offset);
    }

    /**
     *  Add new history line in database
     */
    public function set(string $username, string $action, string $state)
    {
        $mylogin = new \Controllers\Login();

        $username = \Controllers\Common::validateData($username);
        $action   = \Controllers\Common::validateData($action);
        $state    = \Controllers\Common::validateData($state);

        /**
         *  Check if user exists
         */
        if (!$mylogin->userExists($username)) {
            throw new Exception('User ' . $username . ' does not exist');
        }

        /**
         *  Retrieve user Id from username
         */
        $userId = $mylogin->getIdByUsername($username);

        $this->model->set($userId, $action, $state);
    }
}
