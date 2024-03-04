<?php

namespace Controllers\Task\Form\Param;

use Exception;

class GpgCheck
{
    public static function check(string $gpgCheck) : void
    {
        if ($gpgCheck !== 'true' and $gpgCheck !== 'false') {
            throw new Exception('GPG signature check is invalid');
        }
    }
}
