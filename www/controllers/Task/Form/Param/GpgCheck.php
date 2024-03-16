<?php

namespace Controllers\Task\Form\Param;

use Exception;

class GpgCheck
{
    public static function check(string $gpgCheck) : void
    {
        if ($gpgCheck !== "yes" and $gpgCheck !== "no") {
            throw new Exception('GPG signature check is invalid');
        }
    }
}
