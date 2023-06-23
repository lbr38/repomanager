<?php

namespace Controllers\Operation\Param;

use Exception;

class GpgCheck
{
    public static function check(string $gpgCheck)
    {
        if ($gpgCheck !== "yes" and $gpgCheck !== "no") {
            throw new Exception('GPG signature check is invalid');
        }
    }
}
