<?php

namespace Controllers\Task\Form\Param;

use Exception;
use \Controllers\Utils\Validate;

class Group
{
    public static function check(string $group) : void
    {
        if (empty($group)) {
            return;
        }

        if (!Validate::alphaNumericHyphen($group)) {
            throw new Exception('Group contains invalid characters');
        }
    }
}
