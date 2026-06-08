<?php

namespace Controllers\App;

use Controllers\App\Structure\Directory;
use Controllers\App\Structure\File;
use Controllers\App\Config\Notification;
use Controllers\App\Config\Properties;
use Controllers\App\Config\Settings;
use Controllers\App\Config\Main as MainConfig;
use Controllers\App\Config\Env;
use Controllers\App\Config\Log;
use Controllers\App\Permissions;
use Controllers\App\Session;
use Controllers\App\Header;
use Exception;

class Main
{
    public function __construct(string $level = 'all')
    {
        $__LOAD_GENERAL_ERROR  = 0;
        $__LOAD_ERROR_MESSAGES = [];

        if (!in_array($level, ['minimal', 'all', 'api'])) {
            throw new Exception('Invalid autoloader level specified: ' . $level);
        }

        // Load main configuration and create necessary directories and files
        Properties::get();
        MainConfig::get();
        Settings::getYaml();
        Settings::get();
        Env::get();
        Directory::create();
        File::create();

        // Load minimal components, useful for minimal pages or CLI scripts
        if ($level == 'minimal') {
            Permissions::load();
        }

        // Load API components
        if ($level == 'api') {
            Header::authenticate();
            Permissions::load();
        }

        // Load all components
        if ($level == 'all') {
            // Define a cookie with the actual URI, useful to redirect to the same page after logout/login
            if (!empty($_SERVER['REQUEST_URI'])) {
                if ($_SERVER["REQUEST_URI"] != '/login' and $_SERVER["REQUEST_URI"] != '/logout') {
                    // Secure cookie only if HTTPS
                    setcookie('origin', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH), [
                        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),
                        'httponly' => true
                    ]);
                }
            }

            // Load session, permissions, log and notifications
            Session::load();
            Permissions::load();
            Log::get();
            Notification::get();
        }

        // Errors related to the loading of the main configuration
        if (defined('__LOAD_SETTINGS_ERROR') && __LOAD_SETTINGS_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<b>Some settings are not properly configured</b>:";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __LOAD_SETTINGS_MESSAGES);
            $__LOAD_GENERAL_ERROR++;
        }

        // Create dirs errors
        if (defined('__CREATE_DIRS_ERROR') && __CREATE_DIRS_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<b>Some directories could not be generated</b>:";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __CREATE_DIRS_MESSAGES);
            $__LOAD_GENERAL_ERROR++;
        }

        // Create files errors
        if (defined('__CREATE_FILES_ERROR') && __CREATE_FILES_ERROR > 0) {
            $__LOAD_ERROR_MESSAGES[] = "<b>Some files could not be generated</b>:";
            $__LOAD_ERROR_MESSAGES = array_merge($__LOAD_ERROR_MESSAGES, __CREATE_FILES_MESSAGES);
            $__LOAD_GENERAL_ERROR++;
        }

        // Errors related to the loading of the environments
        if (defined('__LOAD_ERROR_EMPTY_ENVS') && __LOAD_ERROR_EMPTY_ENVS > 0) {
            $__LOAD_ERROR_MESSAGES[] = '<b>You must at least configure 1 environment.</b>';
            $__LOAD_GENERAL_ERROR++;
        }

        // Define a constant containing the number of errors encountered
        if (!defined('__LOAD_GENERAL_ERROR')) {
            define('__LOAD_GENERAL_ERROR', $__LOAD_GENERAL_ERROR);
        }

        // Define a constant containing all the error messages
        if (!defined('__LOAD_ERROR_MESSAGES')) {
            define('__LOAD_ERROR_MESSAGES', $__LOAD_ERROR_MESSAGES);
        }

        unset($__LOAD_GENERAL_ERROR, $__LOAD_ERROR_MESSAGES);
    }
}
