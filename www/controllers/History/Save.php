<?php

namespace Controllers\History;

use Exception;

class Save extends History
{
    public function __construct()
    {
        parent::__construct();

        $this->model = new \Models\History\Save();
    }

    /**
     *  Define new history line and save it in database
     *  Optional details:
     *  - category: category of the action (authentication, repository, mirror, task, group, profile, user, settings)
     *  - changes: list of values before and after the action, see Details::diff()
     *  - params: parameters of the action, as label => value
     */
    public static function set(string $action, string $state = 'success', string $username = 'unknown', string $category = '', array $changes = [], array $params = []) : void
    {
        // Create a new instance of self to call non-static method
        $selfController = new \Controllers\History\Save();
        $selfController->save($action, $state, $username, $category, $changes, $params);
        unset($selfController);
    }

    /**
     *  Define a new history line for a task executed or scheduled from a form, with the task parameters
     */
    public static function task(string $description, array $task, string $category = 'repository', array $changes = []) : void
    {
        $prefix = Details::isScheduled($task) ? 'Scheduling task: ' : 'Running task: ';

        self::set($prefix . $description, category: $category, changes: $changes, params: Details::task($task));
    }

    /**
     *  Save a new history line in database
     */
    public function save(string $action, string $state, string $username, string $category = '', array $changes = [], array $params = []) : void
    {
        $id          = '';
        $ip          = '';
        $ipForwarded = '';
        $userAgent   = '';
        $details     = '';

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

            // Details are stored as JSON
            if (!empty($changes) or !empty($params)) {
                $details = json_encode(array_filter(['changes' => $changes, 'params' => $params]), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            // Save history line in database
            $this->model->save($id, $username, $action, $ip, $ipForwarded, $userAgent, $state, $category, $details);
        } catch (Exception $e) {
            throw new Exception('Error during historization: ' . $e->getMessage());
        }
    }
}
