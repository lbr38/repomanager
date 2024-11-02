<?php

namespace Controllers\App\Config;

class System
{
    /**
     *  Load system and OS informations
     */
    public static function get()
    {
        setlocale(LC_ALL, 'en_EN');

        /**
         *  Protocol (http/https)
         */
        if (!defined('__SERVER_PROTOCOL__')) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) and $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
                define('__SERVER_PROTOCOL__', 'https');
            } else {
                define('__SERVER_PROTOCOL__', 'http');
            }
        }

        /**
         *  Server url
         */
        if (!defined('__SERVER_URL__')) {
            if (!empty($_SERVER['HTTP_HOST'])) {
                define('__SERVER_URL__', __SERVER_PROTOCOL__ . '://' . $_SERVER['HTTP_HOST']);
            } else {
                define('__SERVER_URL__', '');
            }
        }

        /**
         *  Server IP
         */
        if (!defined('__SERVER_IP__')) {
            if (!empty($_SERVER['SERVER_ADDR'])) {
                define('__SERVER_IP__', $_SERVER['SERVER_ADDR']);
            } else {
                define('__SERVER_IP__', '');
            }
        }

        /**
         *  URL + URI
         */
        if (!defined('__ACTUAL_URL__')) {
            /**
             *  If sourceUrl is set (POST request from ajax) then we use it
             */
            if (!empty($_POST['sourceUrl'])) {
                define('__ACTUAL_URL__', $_POST['sourceUrl']);
            } else {
                if (!empty($_SERVER['HTTP_HOST']) and !empty($_SERVER['REQUEST_URI'])) {
                    define('__ACTUAL_URL__', __SERVER_PROTOCOL__ . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
                } else {
                    define('__ACTUAL_URL__', '');
                }
            }
        }

        /**
         *  URI
         */
        if (!defined('__ACTUAL_URI__')) {
            /**
             *  If sourceUri is set (POST request from ajax) then we use it
             */
            if (!empty($_POST['sourceUri'])) {
                define('__ACTUAL_URI__', explode('/', $_POST['sourceUri']));
            } else {
                if (!empty($_SERVER["REQUEST_URI"])) {
                    define('__ACTUAL_URI__', explode('/', parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH)));
                } else {
                    define('__ACTUAL_URI__', '');
                }
            }
        }

        /**
         *  If HTTP_X_REQUESTED_WITH is set to 'xmlhttprequest' we can assume that the request is an AJAX request
         */
        if (!defined('AJAX')) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) and strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                define('AJAX', true);
            } else {
                define('AJAX', false);
            }
        }

        /**
         *  Clear cookies starting with 'tables/' or 'temp/' when the page has been reloaded by the user (not AJAX)
         */
        if (AJAX === false) {
            foreach ($_COOKIE as $key => $value) {
                if (strpos($key, 'tables/') === 0 or strpos($key, 'temp/') === 0) {
                    setcookie($key, '', time() - 3600, '/');
                    unset($_COOKIE[$key]);
                }
            }
        }

        /**
         *  GET Parameters
         */
        if (!defined('__GET_PARAMETERS__')) {
            if (!empty($_POST['sourceGetParameters'])) {
                define('__GET_PARAMETERS__', $_POST['sourceGetParameters']);
            } else {
                define('__GET_PARAMETERS__', '');
            }
        }

        /**
         *  Parameters
         */
        if (!defined('__QUERY_STRING__')) {
            if (!empty($_SERVER['QUERY_STRING'])) {
                define('__QUERY_STRING__', parse_url($_SERVER["QUERY_STRING"], PHP_URL_PATH));
            } else {
                define('__QUERY_STRING__', '');
            }
        }
    }
}
