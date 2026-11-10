<?php

namespace Controllers\Task\Form\Param;

use Exception;
use Controllers\Utils\Validate;

class Dist
{
    public static function check(array $dists) : void
    {
        if (empty($dists)) {
            throw new Exception('Distribution name must be specified');
        }

        foreach ($dists as $dist) {
            if (!is_string($dist) or empty($dist) or !Validate::alphaNumeric($dist, ['-', '_', '.', '/'])) {
                throw new Exception('Distribution name cannot contain special characters except hyphen, underscore, dot, slash');
            }

            // The distribution is part of the repository path, prevent path traversal (e.g. '..') and empty or hidden directories
            foreach (explode('/', $dist) as $segment) {
                if ($segment === '' or str_starts_with($segment, '.')) {
                    throw new Exception('Distribution name is invalid');
                }
            }
        }
    }
}
