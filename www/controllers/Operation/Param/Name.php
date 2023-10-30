<?php

namespace Controllers\Operation\Param;

use Exception;

class Name
{
    public static function check(string $name)
    {
        if (empty($name)) {
            throw new Exception('Repository name must be specified');
        }

        if (!\Controllers\Common::isAlphanum($name, array('-', '_'))) {
            throw new Exception('Repository name contains invalid characters');
        }
    }
}
