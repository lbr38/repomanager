<?php

namespace Controllers\Layout\Table;

use Exception;

class Render
{
    public static function render(string $table, int $offset = 0)
    {
        /**
         *  Check if table exists
         */
        if (!file_exists(ROOT . '/views/includes/tables/' . $table . '.inc.php')) {
            throw new Exception('Could not retrieve content: unknow table ' . $table);
        }

        /**
         *  Include vars file if exists
         */
        if (file_exists(ROOT . '/controllers/Layout/Table/vars/' . $table . '.vars.inc.php')) {
            include_once(ROOT . '/controllers/Layout/Table/vars/' . $table . '.vars.inc.php');
        }

        /**
         *  Include table content
         */
        include_once(ROOT . '/views/includes/tables/' . $table . '.inc.php');
    }
}
