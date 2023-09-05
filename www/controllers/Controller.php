<?php

namespace Controllers;

require_once('Autoloader.php');

use Exception;

class Controller
{
    public static function render()
    {
        /**
         *  Getting target URI
         */
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $uri = explode('/', $uri);
        $targetUri = $uri[1];

        /**
         *  If target URI is login or logout then load minimal necessary
         */
        if ($targetUri == 'login' or $targetUri == 'logout') {
            new Autoloader('minimal');
        } else {
            new Autoloader();
        }

        /**
         *  If target URI is 'index.php' then redirect to /
         */
        if ($targetUri == 'index.php') {
            header('Location: /');
        }

        if ($targetUri == '') {
            $targetUri = 'repos';
        }

        /**
         *  Rendering
         */
        $mylayout = new Layout\Layout();

        /**
         *  Render page
         */
        $mylayout->render($targetUri);
    }
}
