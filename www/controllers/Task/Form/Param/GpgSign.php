<?php

namespace Controllers\Task\Form\Param;

use Exception;

class GpgSign
{
    public static function check(string $gpgResign) : void
    {
        if ($gpgResign !== "yes" and $gpgResign !== "no") {
            throw new Exception('GPG signature is invalid');
        }
    }
}
