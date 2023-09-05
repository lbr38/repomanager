<?php

namespace Controllers\Operation\Param;

use Exception;

class PackageType
{
    public static function check(string $type)
    {
        $valid = array('rpm', 'deb');

        if (empty($type)) {
            throw new Exception('Package type must be specified');
        }

        if (!in_array($type, $valid)) {
            throw new Exception('Package type is invalid');
        }
    }
}
