<?php

namespace Controllers\Layout\Panel;

use Exception;

class Render
{
    public static function render(string $panel)
    {
        /**
         *  Check if panel exists
         */
        if (!file_exists(ROOT . '/views/includes/panels/' . $panel . '.inc.php')) {
            throw new Exception('Could not retrieve content: unknow panel ' . $panel);
        }

        /**
         *  Include panel content
         */
        include_once(ROOT . '/views/includes/panels/' . $panel . '.inc.php');
    }
}
