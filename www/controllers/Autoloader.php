<?php

namespace Controllers;

use Exception;

/**
 *  Classe d'autochargement des classes et des constantes
 */

class Autoloader
{
    public function __construct(string $level = 'all')
    {
        $__LOAD_GENERAL_ERROR = 0;
        $__LOAD_ERROR_MESSAGES = array();

        if (!defined('ROOT')) {
            define('ROOT', '/var/www/repomanager');
        }

        $this->register();

        /**
         *  Load minimal components
         *  Useful for login/logout
         */
        if ($level == 'minimal') {
            \Controllers\App\Config\Properties::get();
            \Controllers\App\Config\Main::get();
            \Controllers\App\Config\Settings::get();
            \Controllers\App\Structure\Directory::create();
            \Controllers\App\Structure\File::create();
        }

        /**
         *  Load all components
         */
        if ($level == 'all') {
            /**
             *  Define a cookie with the actual URI
             *  Useful to redirect to the same page after logout/login
             */
            if (!empty($_SERVER['REQUEST_URI'])) {
                if ($_SERVER["REQUEST_URI"] != '/login' and $_SERVER["REQUEST_URI"] != '/logout') {
                    setcookie('origin', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), array('secure' => true, 'httponly' => true));
                }
            }

            \Controllers\App\Config\Properties::get();
            \Controllers\App\Config\Main::get();
            \Controllers\App\Config\Settings::get();
            \Controllers\App\Config\Env::get();
            \Controllers\App\Structure\Directory::create();
            \Controllers\App\Structure\File::create();
            \Controllers\App\Session::load();
            \Controllers\App\Config\Log::get();
            \Controllers\App\Config\Notification::get();
        }

        /**
         *  Load components for API
         */
        if ($level == 'api') {
            \Controllers\App\Config\Properties::get();
            \Controllers\App\Config\Main::get();
            \Controllers\App\Config\Settings::get();
            \Controllers\App\Config\Env::get();
            \Controllers\App\Structure\Directory::create();
            \Controllers\App\Structure\File::create();
        }

        /**
         *  Retrieve all loading errors
         */
        /**
         *  Errors related to the loading of the main configuration
         */
        if (defined('__LOAD_SETTINGS_ERROR') && __LOAD_SETTINGS_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<b>Some settings are not properly configured</b>:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_SETTINGS_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Create dirs errors
         */
        if (defined('__CREATE_DIRS_ERROR') && __CREATE_DIRS_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<br><b>Some directories could not be generated</b>:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __CREATE_DIRS_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Create files errors
         */
        if (defined('__CREATE_FILES_ERROR') && __CREATE_FILES_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<br><b>Some files could not be generated</b>:<br>";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __CREATE_FILES_MESSAGES);
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Errors related to the loading of the environments
         */
        if (defined('__LOAD_ERROR_EMPTY_ENVS') && __LOAD_ERROR_EMPTY_ENVS > 0) {
            $__LOAD_ERROR_MESSAGES[] = '<br><b>You must at least configure 1 environment.</b>';
            ++$__LOAD_GENERAL_ERROR;
        }

        /**
         *  Define a constant containing the number of errors encountered
         */
        if (!defined('__LOAD_GENERAL_ERROR')) {
            define('__LOAD_GENERAL_ERROR', $__LOAD_GENERAL_ERROR);
        }

        /**
         *  Define a constant containing all the error messages
         */
        if (!defined('__LOAD_ERROR_MESSAGES')) {
            define('__LOAD_ERROR_MESSAGES', $__LOAD_ERROR_MESSAGES);
        }

        unset($__LOAD_GENERAL_ERROR, $__LOAD_ERROR_MESSAGES);
    }

    /**
     *  Class autoload
     */
    private function register()
    {
        spl_autoload_register(function ($className) {
            $className = str_replace('\\', '/', $className);
            $className = str_replace('Models', 'models', $className);
            $className = str_replace('Controllers', 'controllers', $className);
            $className = str_replace('Views', 'views', $className);

            if (file_exists(ROOT . '/' . $className . '.php')) {
                require_once(ROOT . '/' . $className . '.php');
            }
        });
    }
}
