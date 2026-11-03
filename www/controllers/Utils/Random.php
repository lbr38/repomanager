<?php

namespace Controllers\Utils;

class Random
{
    /**
     *  Generates a random string
     */
    public static function string(int $length) : string
    {
        return self::generate($length, 'abcdefghijklmnopqrstuvwxyz');
    }

    /**
     *  Generate random strong string (letters, numbers and special characters)
     */
    public static function strongString(int $length, string|null $characters = null) : string
    {
        $characters ??= '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*%-_{}()';

        return self::generate($length, $characters);
    }

    /**
     *  Generate a random string of the specified length from the specified characters
     *  Uses a cryptographically secure random number generator
     */
    private static function generate(int $length, string $characters) : string
    {
        $randomString = '';
        $max = strlen($characters) - 1;

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $max)];
        }

        return $randomString;
    }
}
