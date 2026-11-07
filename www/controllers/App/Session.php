<?php

namespace Controllers\App;

class Session
{
    /**
     *  Start the session (if not already active) with secure options
     */
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_secure'   => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                // Reject uninitialized session Ids (prevents session fixation with an Id chosen by an attacker)
                'use_strict_mode' => true,
            ]);
        }
    }

    /**
     *  Start and check actual session
     */
    public static function load()
    {
        /**
         *  Start session
         */
        self::start();

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
        self::start();

        foreach ($variables as $name => $value) {
            $_SESSION[$name] = $value;
        }

        session_write_close();
    }

    /**
     *  Generate a new session Id and delete the old session, to be called when the user logs in
     *  Prevents session fixation: an Id known before authentication can not be used once the user is authenticated
     */
    public static function regenerate(): void
    {
        self::start();

        session_regenerate_id(true);

        session_write_close();
    }
}
