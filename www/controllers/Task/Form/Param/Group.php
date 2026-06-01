<?php

namespace Controllers\Task\Form\Param;

use Exception;
use Controllers\Group\Repo as RepoGroup;

class Group
{
    public static function check(string $group) : void
    {
        $groupController = new RepoGroup();

        if (empty($group)) {
            return;
        }

        if (!$groupController->exists($group)) {
            throw new Exception('Group ' . $group . ' does not exist');
        }
    }
}
