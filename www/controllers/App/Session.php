<?php

namespace Controllers\App;

use Exception;

class Session
{
    /**
     *  Start and check actual session
     */
    public static function load()
    {
        /**
         *  Start session
         */
        if (!isset($_SESSION)) {
            session_start();
        }

        /**
         *  If username and role session variables are empty then redirect to login page
         */
        if (empty($_SESSION['username']) or empty($_SESSION['role'])) {
            header('Location: /logout');
            exit();
        }

        /**
         *  If session has reached 60min timeout then redirect to logout page
         */
        if (isset($_SESSION['start_time']) && (time() - $_SESSION['start_time'] > SESSION_TIMEOUT)) {
            header('Location: /logout');
            exit();
        }

        /**
         *  Define the new session start time (or renew the current session)
         */
        $_SESSION['start_time'] = time();

        /**
         *  Define IS_ADMIN
         */
        if (!defined('IS_ADMIN')) {
            if ($_SESSION['role'] === 'super-administrator' or $_SESSION['role'] === 'administrator') {
                define('IS_ADMIN', true);
            } else {
                define('IS_ADMIN', false);
            }
        }

        /**
         *  Define IS_SUPERADMIN
         */
        if (!defined('IS_SUPERADMIN')) {
            if ($_SESSION['role'] === 'super-administrator') {
                define('IS_SUPERADMIN', true);
            } else {
                define('IS_SUPERADMIN', false);
            }
        }
    }
}
