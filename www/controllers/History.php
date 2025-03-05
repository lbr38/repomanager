<?php

namespace Controllers;

use Exception;

class History
{
    private $model;
    private $username;

    public function __construct()
    {
        $this->model = new \Models\History();
    }

    public function setUsername(string $username) : void
    {
        $this->username = $username;
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
    public function set(string $action, string $state) : void
    {
        $id          = '';
        $username    = 'unknown';
        $ip          = '';
        $ipForwarded = '';
        $userAgent   = '';

        try {
            $userController = new \Controllers\User\User();
            $action   = \Controllers\Common::validateData($action);
            $state    = \Controllers\Common::validateData($state);

            if (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }

            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ipForwarded = $_SERVER['HTTP_X_FORWARDED_FOR'];
            }

            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $userAgent = $_SERVER['HTTP_USER_AGENT'];
            }

            /**
             *  By default, use the username stored in session
             *  Otherwise, if an username has been set, use it
             */
            if (!empty($_SESSION['username'])) {
                $username = $_SESSION['username'];
            }
            if (!empty($this->username)) {
                $username = $this->username;
            }

            /**
             *  If an Id is set in session, use it
             */
            if (!empty($_SESSION['id'])) {
                $id = $_SESSION['id'];
            }

            /**
             *  Add history line in database
             */
            $this->model->set($id, $username, $action, $ip, $ipForwarded, $userAgent, $state);
        } catch (Exception $e) {
            throw new Exception('Error during historization: ' . $e->getMessage());
        }
    }
}
