<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Environment
{
    public static function check(array $envs): void
    {
        $envController = new \Controllers\Environment();

        if (empty($envs)) {
            throw new Exception('Environment must be specified');
        }

        foreach ($envs as $env) {
            if (!$envController->exists($env)) {
                throw new Exception('Specified environment does not exist');
            }
        }

        // Check that the environment is not protected
        foreach ($envs as $env) {
            if ($envController->isProtected($env)) {
                throw new Exception('Environment ' . $env . ' is protected and cannot be modified');
            }
        }

        unset($envs, $envController);
    }
}
