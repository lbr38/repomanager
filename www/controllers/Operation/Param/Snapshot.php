<?php

namespace Controllers\Operation\Param;

use Exception;

class Snapshot
{
    public static function checkId(int $id)
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
