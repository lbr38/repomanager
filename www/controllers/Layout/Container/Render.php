<?php

namespace Controllers\Layout\Container;

use Exception;

class Render
{
    public static function render(string $container)
    {
        /**
         *  Include container content
         */
        if (!file_exists(ROOT . '/views/includes/containers/' . $container . '.inc.php')) {
            throw new Exception('Could not retrieve content: unknow container ' . $container);
        }

        /**
         *  Include vars file if exists
         */
        if (file_exists(ROOT . '/controllers/Layout/Container/vars/' . $container . '.vars.inc.php')) {
            include_once(ROOT . '/controllers/Layout/Container/vars/' . $container . '.vars.inc.php');
        }

        include_once(ROOT . '/views/includes/containers/' . $container . '.inc.php');
    }
}
