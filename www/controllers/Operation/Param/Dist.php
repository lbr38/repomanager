<?php

namespace Controllers\Operation\Param;

use Exception;

class Dist
{
    public static function check(string $dist)
    {
        if (empty($dist)) {
            throw new Exception('Distribution name must be specified');
        }

        if (!\Controllers\Common::isAlphanum($dist, array('-', '/'))) {
            throw new Exception('Distribution name cannot contain special characters except hyphen');
        }
    }
}
