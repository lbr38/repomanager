<?php

namespace Controllers\Task\Form\Param;

use Exception;

class PackageType
{
    public static function check(string $type) : void
    {
        $valid = ['rpm', 'deb'];

        if (empty($type)) {
            throw new Exception('Package type must be specified');
        }

        if (!in_array($type, $valid)) {
            throw new Exception('Package type ' . $type . ' is invalid');
        }
    }
}
