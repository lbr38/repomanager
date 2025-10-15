<?php

namespace Controllers\Task\Form\Param;

use Exception;
use \Controllers\Utils\Validate;

class Name
{
    public static function check(string $name) : void
    {
        if (empty($name)) {
            throw new Exception('Repository name must be specified');
        }

        if (!Validate::alphaNumericHyphen($name)) {
            throw new Exception('Repository name contains invalid characters');
        }
    }
}
