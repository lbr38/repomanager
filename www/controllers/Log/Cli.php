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

    /**
     *  Returns current date and time
     */
    public static function date()
    {
        return '[' . date('D M j H:i:s') . ']';
    }
}
