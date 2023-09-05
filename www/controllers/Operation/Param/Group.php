<?php

namespace Controllers\Operation\Param;

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
