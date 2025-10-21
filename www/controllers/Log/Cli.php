<?php

namespace Controllers\Log;

use Controllers\App\DebugMode;

class Cli
{
    /**
     *  Print a message to the console
     */
    public static function log(string $message) : void
    {
        echo self::date() . '[INF] ' . $message . PHP_EOL;
    }

    /**
     *  Print a red error message to the console
     */
    public static function error(string $title, string $message) : void
    {
        echo self::date() . "[ERR]\033[31m " . $title . ':' . PHP_EOL;
        echo $message . "\033[0m" . PHP_EOL;
    }

    /**
     *  Print a yellow warning message to the console
     */
    public static function warning(string $message, string $title = null) : void
    {
        if (!is_null($title)) {
            echo self::date() . "[WRN]\033[33m " . $title . ':' . PHP_EOL;
            echo $message . "\033[0m" . PHP_EOL;
        } else {
            echo self::date() . "[WRN]\033[33m " . $message . "\033[0m" . PHP_EOL;
        }
    }

    /**
     *  Print a debug message to the console
     */
    public static function debug(string $message) : void
    {
        if (!DebugMode::enabled()) {
            return;
        }

        echo self::date() . '[DBG] ' . $message . PHP_EOL;
    }

    /**
     *  Returns the current date and time
     */
    public static function date() : string
    {
        return '[' . date('D M j H:i:s') . ']';
    }
}
