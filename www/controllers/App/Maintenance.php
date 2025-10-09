<?php

namespace Controllers\App;

use Exception;

class Maintenance
{
    /**
     *  Enable / disable maintenance
     */
    public static function set(string $status) : void
    {
        // Create a 'maintenance' file to enable maintenance page on the site
        if ($status == 'on') {
            if (!file_exists(DATA_DIR . '/maintenance')) {
                if (!touch(DATA_DIR . '/maintenance')) {
                    throw new Exception('Cannot enable maintenance mode');
                }
            }
        }

        // Remove 'maintenance' file to disable maintenance page on the site
        if ($status == 'off') {
            if (file_exists(DATA_DIR . '/maintenance')) {
                if (!unlink(DATA_DIR . '/maintenance')) {
                    throw new Exception('Cannot disable maintenance mode');
                }
            }
        }
    }
}
