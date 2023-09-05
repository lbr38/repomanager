<?php

namespace Controllers\App\Structure;

class Directory
{
    /**
     *  Create app directories if not exist
     */
    public static function create()
    {
        $__CREATE_DIRS_ERROR = 0;
        $__CREATE_DIRS_MESSAGES = array();

        $dirs = array(
            DB_DIR,
            GPGHOME,
            LOGS_DIR,
            MAIN_LOGS_DIR,
            POOL,
            PID_DIR,
            TEMP_DIR,
            HOSTS_DIR,
            WWW_CACHE,
            DB_UPDATE_DONE_DIR
        );

        foreach ($dirs as $dir) {
            if (is_dir($dir)) {
                continue;
            }

            if (!mkdir($dir, 0770, true)) {
                $__CREATE_DIRS_ERROR++;
                $__CREATE_DIRS_MESSAGES[] = 'Cannot create directory: ' . $dir;
            }
        }

        if (!defined('__CREATE_DIRS_ERROR')) {
            define('__CREATE_DIRS_ERROR', $__CREATE_DIRS_ERROR);
        }
        if (!defined('__CREATE_DIRS_MESSAGES')) {
            define('__CREATE_DIRS_MESSAGES', $__CREATE_DIRS_MESSAGES);
        }

        unset($__CREATE_DIRS_ERROR, $__CREATE_DIRS_MESSAGES);
    }
}
