<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Arch
{
    public static function check(array $targetArch) : void
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
