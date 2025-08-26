<?php

namespace Controllers\Log;

use Exception;

class Cli
{
    /**
     *  Print a message to the console
     */
    public static function log(string $message)
    {
        echo self::date() . ' ' . $message . PHP_EOL;
    }

    /**
     *  Print a red error message to the console
     */
    public static function error(string $title, string $message)
    {
        echo self::date() . "\033[31m " . $title . ':' . PHP_EOL;
        echo $message . "\033[0m" . PHP_EOL;
    }

    public static function warning(string $message, string $title = null)
    {
        if (!is_null($title)) {
            echo self::date() . "\033[33m " . $title . ':' . PHP_EOL;
            echo $message . "\033[0m" . PHP_EOL;
        } else {
            echo self::date() . "\033[33m " . $message . "\033[0m" . PHP_EOL;
            return;
        }
    }

    /**
     *  Returns current date and time
     */
    public static function date()
    {
        return '[' . date('D M j H:i:s') . ']';
    }
}
