<?php

namespace Controllers\App;

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
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
                'cookie_httponly' => true,
            ]);
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
         *  Release the session file lock immediately
         *  Otherwise PHP keeps an exclusive lock on the session file for the whole request duration, which serializes all the concurrent
         *  requests of the same user (e.g. slow ajax calls would block every other action of the interface)
         *  Any code that needs to persist new session variables must use Session::set()
         */
        session_write_close();
    }

    /**
     *  Update session variables
     *  The session is closed right after being loaded, so it must be reopened to persist any change
     */
    public static function set(array $variables) : void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        foreach ($variables as $name => $value) {
            $_SESSION[$name] = $value;
        }

        session_write_close();
    }
}
