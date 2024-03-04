<?php

namespace Controllers\Task\Form\Param;

use Exception;

class GpgSign
{
    public static function check(string $gpgResign) : void
    {
        if ($gpgResign !== 'true' and $gpgResign !== 'false') {
            throw new Exception('GPG signature is invalid');
        }
    }
}
