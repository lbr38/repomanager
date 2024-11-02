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
     */
    public function getAll()
    {
        return $this->model->getAll();
    }

    /**
     *  Retrieve all history from a user
     */
    public function getByUser(int $userId)
    {
        $userId = \Controllers\Common::validateData($userId);

        return $this->model->getByUser($userId);
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
