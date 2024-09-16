<?php

namespace Controllers\Task\Form\Param;

use Exception;

class PackageExclude
{
    public static function check(array $packages) : void
    {
        if (empty($packages)) {
            return;
        }

        foreach ($packages as $package) {
            if (!\Controllers\Common::isAlphanumdash($package, array('.*', '.'))) {
                throw new Exception('List of packages to exclude contains invalid characters');
            }
        }
    }
}
