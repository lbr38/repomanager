<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Releasever
{
    public static function check(array $releasevers) : void
    {
        if (empty($releasevers)) {
            throw new Exception('Release version must be specified');
        }

        foreach ($releasevers as $releasever) {
            // is_numeric() accepts leading and trailing whitespaces, which cannot be part of the repository path
            if (!is_numeric($releasever) or preg_match('/\s/', strval($releasever))) {
                throw new Exception('Release version must be numeric');
            }
        }
    }
}
