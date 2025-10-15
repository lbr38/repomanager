<?php

namespace Controllers\Task\Form\Param;

use Exception;
use \Controllers\Utils\Validate;

class Arch
{
    public static function check(array $archs) : void
    {
        if (empty($archs)) {
            throw new Exception('Architecture must be specified');
        }

        foreach ($archs as $arch) {
            if (!Validate::alphaNumericHyphen($arch)) {
                throw new Exception('Architecture contains invalid characters');
            }
        }
    }
}
