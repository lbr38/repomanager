<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Type
{
    public static function check(string $type)
    {
        if (empty($type)) {
            throw new Exception('Repository type must be specified');
        }

        if ($type !== 'mirror' and $type !== 'local') {
            throw new Exception('Invalid repository type');
        }
    }
}
