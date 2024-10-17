<?php

namespace Controllers\Layout\Panel;

use Exception;

class Render
{
    public static function render(string $panel, array|null $item = null)
    {
        /**
         *  Check if panel exists
         */
        if (!file_exists(ROOT . '/views/includes/panels/' . $panel . '.inc.php')) {
            throw new Exception('Could not retrieve content: unknown panel ' . $panel);
        }

        /**
         *  Include vars file if exists
         */
        if (file_exists(ROOT . '/controllers/Layout/Panel/vars/' . $panel . '.vars.inc.php')) {
            include_once(ROOT . '/controllers/Layout/Panel/vars/' . $panel . '.vars.inc.php');
        }

        /**
         *  Include panel content
         */
        include_once(ROOT . '/views/includes/panels/' . $panel . '.inc.php');
    }
}
