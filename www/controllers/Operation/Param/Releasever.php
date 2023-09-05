<?php

namespace Controllers\Operation\Param;

use Exception;

class Releasever
{
    public static function check(array $releasevers)
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
