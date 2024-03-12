<?php

namespace Controllers\Task\Form\Param;

use Exception;

class GpgResign
{
    public static function check(string $gpgResign)
    {
        if ($gpgResign !== "yes" and $gpgResign !== "no") {
            throw new Exception('GPG signature is invalid');
        }
    }
}
