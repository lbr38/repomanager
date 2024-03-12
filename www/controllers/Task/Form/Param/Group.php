<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Group
{
    public static function check(string $group)
    {
        if (empty($group)) {
            return;
        }

        if (!\Controllers\Common::isAlphanumDash($group, array('-'))) {
            throw new Exception('Group contains invalid characters');
        }
    }
}
