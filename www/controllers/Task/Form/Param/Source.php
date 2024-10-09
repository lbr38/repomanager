<?php

namespace Controllers\Task\Form\Param;

use Exception;

class Source
{
    public static function check(string $source, string $packageType) : void
    {
        $mysource = new \Controllers\Repo\Source\Source();

        if (empty($source)) {
            throw new Exception('Source repository must be specified');
        }

        if (!$mysource->exists($packageType, $source)) {
            throw new Exception('Source repository does not exist');
        }

        unset($mysource);
    }
}
