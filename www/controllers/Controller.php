<?php

namespace Controllers;

require_once('Autoloader.php');

use Exception;

class Controller
{
    public static function render()
    {
        try {
            new Autoloader();

            $mylayout = new Layout\Layout();
            $level = 'all';

            /**
             *  Getting target URI
             */
            $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $uri = explode('/', $uri);
            $targetUri = $uri[1];

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
             *  If target URI is login or logout then load minimal necessary
             */
            if (in_array($targetUri, ['login', 'logout'])) {
                $level = 'minimal';
            }

            /**
             *  Load application components
             */
            new \Controllers\App\Main($level);

            /**
             *  Render page
             */
            $mylayout->render($targetUri);

        // Catch unexpected exceptions and errors
        } catch (Exception | Error $e) {
            $errorMessage = $e->getMessage();
            include_once(ROOT . '/public/custom_errors/custom_50x.php');
        }
    }
}
