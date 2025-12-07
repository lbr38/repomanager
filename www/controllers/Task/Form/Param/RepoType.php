<?php

namespace Controllers\Task\Form\Param;

use Exception;

class RepoType
{
    public static function check(string $type) : void
    {
        $valid = ['mirror', 'local'];

        if (empty($type)) {
            throw new Exception('Repository type must be specified');
        }

        if (!in_array($type, $valid)) {
            throw new Exception('Invalid repository type');
        }
    }
}
