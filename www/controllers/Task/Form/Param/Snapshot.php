<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Snapshot
{
    public static function checkId(int $id) : void
    {
        $myrepo = new \Controllers\Repo\Repo();

        if (empty($id)) {
            throw new Exception('Snapshot id must be specified');
        }

        if (!is_numeric($id)) {
            throw new Exception('Snapshot id must be numeric');
        }

        if (!$myrepo->existsSnapId($id)) {
            throw new Exception('Specified snapshot does not exist');
        }

        unset($myrepo);
    }
}
