<?php

namespace Controllers\Task\Form\Param;

use Exception;
use \Controllers\Utils\Validate;

class PackageExclude
{
    public static function check(array $packages) : void
    {
        if (empty($packages)) {
            return;
        }

        foreach ($packages as $package) {
            if (!Validate::alphaNumericHyphen($package, ['.*', '.'])) {
                throw new Exception('List of packages to exclude contains invalid characters');
            }
        }
    }
}
