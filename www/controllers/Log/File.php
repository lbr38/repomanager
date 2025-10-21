<?php

namespace Controllers\Log;

use Controllers\App\DebugMode;

class File
{
    /**
     *  Write a message to the file
     */
    public static function log(string $file, string $message) : void
    {
        file_put_contents($file, self::date() . '[INF] ' . $message . PHP_EOL, FILE_APPEND);
    }

    /**
     *  Write an error message to the file
     */
    public static function error(string $file, string $message, string $title = null) : void
    {
        if (!is_null($title)) {
            file_put_contents($file, self::date() . '[ERR] ' . $title . ':' . PHP_EOL, FILE_APPEND);
            file_put_contents($file, $message . PHP_EOL, FILE_APPEND);
            return;
        }

        file_put_contents($file, self::date() . '[ERR] ' . $message . PHP_EOL, FILE_APPEND);
    }

    /**
     *  Write a warning message to the file
     */
    public static function warning(string $file, string $message, string $title = null) : void
    {
        if (!is_null($title)) {
            file_put_contents($file, self::date() . '[WRN] ' . $title . ':' . PHP_EOL, FILE_APPEND);
            file_put_contents($file, $message . PHP_EOL, FILE_APPEND);
            return;
        }

        file_put_contents($file, self::date() . '[WRN] ' . $message . PHP_EOL, FILE_APPEND);
    }

    /**
     *  Write a debug message to the file
     */
    public static function debug(string $file, string $message) : void
    {
        if (!DebugMode::enabled()) {
            return;
        }

        file_put_contents($file, self::date() . '[DBG] ' . $message . PHP_EOL, FILE_APPEND);
    }

    /**
     *  Returns the current date and time
     */
    public static function date() : string
    {
        return '[' . date('D M j H:i:s') . ']';
    }
}
