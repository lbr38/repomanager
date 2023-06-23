<?php

namespace Controllers\Operation\Param;

use Exception;

class Source
{
    public static function check(string $source, string $packageType)
    {
        $mysource = new \Controllers\Source();

        if (empty($source)) {
            throw new Exception('Source repository must be specified');
        }

        if (!$mysource->exists($packageType, $source)) {
            throw new Exception('Source repository does not exist');
        }

        unset($mysource);
    }
}
