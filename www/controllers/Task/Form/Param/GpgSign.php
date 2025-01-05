<?php

namespace Controllers\Task\Form\Param;

use Exception;

class GpgSign
{
    public static function check(string $gpgSign) : void
    {
        if ($gpgSign !== 'true' and $gpgSign !== 'false') {
            throw new Exception('GPG signature is invalid');
        }
    }
}
