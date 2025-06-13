<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Environment
{
    public static function check(array $envs) : void
    {
        $myenv = new \Controllers\Environment();

        if (empty($envs)) {
            throw new Exception('Environment must be specified');
        }

        foreach ($envs as $env) {
            if (!$myenv->exists($env)) {
                throw new Exception('Specified environment does not exist');
            }
        }

        unset($envs, $myenv);
    }
}
