<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Dist
{
    public static function check(array $dists) : void
    {
        if (empty($dists)) {
            throw new Exception('Distribution name must be specified');
        }

        foreach ($dists as $dist) {
            if (!\Controllers\Common::isAlphanum($dist, array('-', '_', '.', '/'))) {
                throw new Exception('Distribution name cannot contain special characters except hyphen');
            }
        }
    }
}
