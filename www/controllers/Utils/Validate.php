<?php

namespace Controllers\Utils;

class Validate
{
    /**
     *  Validate a string and return it
     */
    public static function string(string|int $string) : string|int
    {
        return htmlspecialchars(stripslashes(trim($string)));
    }

    /**
     *  Check that the string contains only letters and numbers, additional valid characters can be passed in argument
     */
    public static function alphaNumeric(string $string, array $additionnalValidCharacters = []) : bool
    {
        // If an empty string has been passed, it's valid
        if (empty($string)) {
            return true;
        }

        // If additional valid characters have been passed as argument, we ignore them in the test by replacing them with an empty string
        if (!empty($additionnalValidCharacters)) {
            if (!ctype_alnum(str_replace($additionnalValidCharacters, '', $string))) {
                return false;
            }

        // If no additional valid characters have been passed as argument, we simply test the string with ctype_alnum
        } else {
            if (!ctype_alnum($string)) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Check that the string contains only letters and numbers, hyphen and underscore, additional valid characters can be passed in argument
     */
    public static function alphaNumericHyphen(string $string, array $additionnalValidCharacters = []) : bool
    {
        // Merge the default valid characters with any additional valid characters passed as argument
        $validCharacters = ['-', '_'];

        if (!empty($additionnalValidCharacters)) {
            $validCharacters = array_merge($validCharacters, $additionnalValidCharacters);
        }

        return self::alphaNumeric($string, $validCharacters);
    }

    /**
     *  Validate an email address
     */
    public static function email(string $mail) : bool
    {
        if (filter_var(trim($mail), FILTER_VALIDATE_EMAIL)) {
            return true;
        }

        return false;
    }
}
