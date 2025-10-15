<?php

namespace Controllers\Task\Form\Param;

use Exception;
use \Controllers\Utils\Validate;

class Dist
{
    public static function check(array $dists) : void
    {
        if (empty($dists)) {
            throw new Exception('Distribution name must be specified');
        }

        foreach ($dists as $dist) {
            if (!Validate::alphaNumeric($dist, ['-', '_', '.', '/'])) {
                throw new Exception('Distribution name cannot contain special characters except hyphen');
            }
        }
    }
}
