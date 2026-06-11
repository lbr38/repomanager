<?php

namespace Controllers\Task\Form\Param;

use Controllers\Repo\Repo as RepoController;
use Exception;

class Repo
{
    public static function checkId(int $id) : void
    {
        $repoController = new RepoController();

        if (!$repoController->existsId($id)) {
            throw new Exception('Specified repository does not exist');
        }

        unset($repoController);
    }
}
