<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Name
{
    public static function check(string $name) : void
    {
        if (empty($name)) {
            throw new Exception('Repository name must be specified');
        }

        if (!\Controllers\Common::isAlphanum($name, array('-', '_'))) {
            throw new Exception('Repository name contains invalid characters');
        }
    }
}
