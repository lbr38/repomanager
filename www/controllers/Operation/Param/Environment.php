<?php

namespace Controllers\Operation\Param;

use Exception;

class Environment
{
    public static function check(string $env)
    {
        $myenv = new \Controllers\Environment();

        if (empty($env)) {
            throw new Exception('Environment must be specified');
        }

        if (!$myenv->exists($env)) {
            throw new Exception('Specified environment does not exist');
        }

        unset($myenv);
    }
}
