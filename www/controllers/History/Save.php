<?php

namespace Controllers\History;

use Exception;

/**
 *  Classe regroupant quelques fonctions communes / génériques
 */

class Save extends History
{
    public function __construct()
    {
        parent::__construct();

        $this->model = new \Models\History\Save();
    }

    /**
     *  Define new history line and save it in database
     */
    public static function set(string $action, string $state = 'success', string $username = 'unknown') : void
    {
        // Create a new instance of self to call non-static method
        $selfController = new \Controllers\History\Save();
        $selfController->save($action, $state, $username);
        unset($selfController);
    }

    /**
     *  Save a new history line in database
     */
    public function save(string $action, string $state, string $username) : void
    {
        $id          = '';
        $ip          = '';
        $ipForwarded = '';
        $userAgent   = '';

        try {
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
             *  Use the username stored in session
             *  Otherwise, the username will be 'unknown' or the one specified in parameter
             */
            if (!empty($_SESSION['username'])) {
                $username = $_SESSION['username'];
            }

            // If an Id is set in session, use it
            if (!empty($_SESSION['id'])) {
                $id = $_SESSION['id'];
            }

            // Save history line in database
            $this->model->save($id, $username, $action, $ip, $ipForwarded, $userAgent, $state);
        } catch (Exception $e) {
            throw new Exception('Error during historization: ' . $e->getMessage());
        }
    }
}
