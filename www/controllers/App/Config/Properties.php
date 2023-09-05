<?php

namespace Controllers\App\Config;

class Properties
{
    public static function get()
    {
        if (!file_exists(ROOT . '/config/properties.php')) {
            return;
        }

        /**
         *  Include the properties file
         */
        include_once(ROOT . '/config/properties.php');

        if (empty($config)) {
            return;
        }

        /**
         *  Define each property as a uppercase constant
         */
        foreach ($config as $key => $value) {
            if (!defined(strtoupper($key))) {
                define(strtoupper($key), $value);
            }
        }
    }
}
