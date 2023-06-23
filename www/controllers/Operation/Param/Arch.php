<?php

namespace Controllers\Operation\Param;

use Exception;

class Arch
{
    public static function check(array $targetArch)
    {
        if (empty($targetArch)) {
            throw new Exception('Architecture must be specified');
        }

        foreach ($targetArch as $arch) {
            if (!\Controllers\Common::isAlphanumdash($arch)) {
                throw new Exception('Architecture contains invalid characters');
            }
        }
    }
}
