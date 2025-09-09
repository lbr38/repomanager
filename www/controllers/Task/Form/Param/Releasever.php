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
            if (!is_numeric($releasever)) {
                throw new Exception('Release version must be numeric');
            }
        }
    }
}
