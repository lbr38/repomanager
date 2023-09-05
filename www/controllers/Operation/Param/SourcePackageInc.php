<?php

namespace Controllers\Operation\Param;

use Exception;

class SourcePackageInc
{
    public static function check(string $targetSourcePackage)
    {
        if (empty($targetSourcePackage)) {
            throw new Exception('Source package inclusion must be specified');
        }

        if ($targetSourcePackage !== "yes" and $targetSourcePackage !== "no") {
            throw new Exception('Source package inclusion value is invalid');
        }
    }
}
