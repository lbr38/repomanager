<?php

namespace Controllers\App\Config;

use Controllers\Environment;

class Env
{
    /**
     *  Load environments
     */
    public static function get(): void
    {
        $envController = new Environment();

        if (!defined('ENVS')) {
            define('ENVS', $envController->listAll());
        }
        if (!defined('DEFAULT_ENV')) {
            define('DEFAULT_ENV', $envController->default());
        }

        // If there is no environment configured then __LOAD_ERROR_EMPTY_ENVS = 1
        if (empty(ENVS)) {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) {
                define('__LOAD_ERROR_EMPTY_ENVS', 1);
            }
        } else {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) {
                define('__LOAD_ERROR_EMPTY_ENVS', 0);
            }
        }

        unset($envController);
    }
}
