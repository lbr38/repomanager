<?php

namespace Controllers;

use Exception;

/**
 *  Autoloading class for loading classes and constants
 */
class Autoloader
{
    public function __construct()
    {
        if (!defined('ROOT')) {
            define('ROOT', '/var/www/repomanager');
        }

        spl_autoload_register(function ($className) {
            $className = str_replace('\\', '/', $className);
            $className = str_replace('Models', 'models', $className);
            $className = str_replace('Controllers', 'controllers', $className);
            $className = str_replace('Views', 'views', $className);

            if (!file_exists(ROOT . '/' . $className . '.php')) {
                throw new Exception('Unknown class / class file not found: ' . ROOT . '/' . $className . '.php');
            }

            require_once(ROOT . '/' . $className . '.php');
        });
    }
}
