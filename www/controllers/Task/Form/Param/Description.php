<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Description
{
    public static function check(string $description = null) : void
    {
        if (empty($description)) {
            return;
        }

        // Description cannot contain single quotes or backslashes
        if (str_contains($description, "'") || str_contains($description, "\\")) {
            throw new Exception('Description contains invalid characters');
        }
    }
}
