<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Arch
{
    public static function check(array $archs) : void
    {
        if (empty($archs)) {
            throw new Exception('Architecture must be specified');
        }

        foreach ($archs as $arch) {
            if (!\Controllers\Common::isAlphanumdash($arch)) {
                throw new Exception('Architecture contains invalid characters');
            }
        }
    }
}
