<?php

namespace Controllers\App\Config;

use Exception;

class Env
{
    /**
     *  Load environments
     */
    public static function get()
    {
        $myenv = new \Controllers\Environment();

        if (!defined('ENVS')) {
            define('ENVS', $myenv->listAll());
        }
        if (!defined('ENVS_TOTAL')) {
            define('ENVS_TOTAL', $myenv->total());
        }
        if (!defined('DEFAULT_ENV')) {
            define('DEFAULT_ENV', $myenv->default());
        }
        if (!defined('LAST_ENV')) {
            define('LAST_ENV', $myenv->last());
        }

        /**
         *  If there is no environment configured then __LOAD_ERROR_EMPTY_ENVS = 1
         */
        if (empty(ENVS)) {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) {
                define('__LOAD_ERROR_EMPTY_ENVS', 1);
            }
        } else {
            if (!defined('__LOAD_ERROR_EMPTY_ENVS')) {
                define('__LOAD_ERROR_EMPTY_ENVS', 0);
            }
        }

        unset($myenv);
    }
}
