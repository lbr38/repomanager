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

        /**
         *  Only allow one release version selection
         *  May be in the future it will be possible to select multiple release versions
         */
        if (count($releasevers) > 1) {
            throw new Exception('Only one release version can be selected');
        }
    }
}
