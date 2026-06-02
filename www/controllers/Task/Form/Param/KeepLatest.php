<?php

namespace Controllers\Task\Form\Param;

use Exception;

class KeepLatest
{
    public static function check(string $value): void
    {
        if (empty($value)) {
            return;
        }

        if (!is_numeric($value)) {
            throw new Exception('Keep latest versions of packages value must be a number.');
        }

        if ($value < 1) {
            throw new Exception('Keep latest versions of packages value must be greater than 0.');
        }
    }
}
