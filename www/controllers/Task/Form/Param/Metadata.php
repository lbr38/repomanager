<?php

namespace Controllers\Task\Form\Param;

use Exception;
use Controllers\Utils\Validate;

class Metadata
{
    public static function checkOrigin(string $origin): void
    {
        if (empty($origin)) {
            return;
        }

        if (!Validate::alphaNumeric($origin, ['-', '_', '.', '>', '<', '+', ' '])) {
            throw new Exception('Metadata Origin field cannot contains invalid characters.');
        }
    }

    public static function checkLabel(string $label): void
    {
        if (empty($label)) {
            return;
        }

        if (!Validate::alphaNumeric($label, ['-', '_', '.', '>', '<', '+', ' '])) {
            throw new Exception('Metadata Label field cannot contains invalid characters.');
        }
    }

    public static function checkDescription(string $description): void
    {
        if (empty($description)) {
            return;
        }

        if (!Validate::alphaNumeric($description, ['-', '_', '.', '>', '<', '+', ' '])) {
            throw new Exception('Metadata Description field cannot contains invalid characters.');
        }
    }
}
