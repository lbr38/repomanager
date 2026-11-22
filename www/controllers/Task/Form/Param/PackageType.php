<?php

namespace Controllers\Task\Form\Param;

use Exception;

class PackageType
{
    /**
     *  Check the package type of a curated or local repository (arch, alpine and openbsd repositories can only be mirrors)
     */
    public static function check(string $type) : void
    {
        $valid = ['rpm', 'deb'];

        if (empty($type)) {
            throw new Exception('Package type must be specified');
        }

        if (!in_array($type, $valid)) {
            throw new Exception('Package type ' . $type . ' is invalid');
        }

        self::checkEnabled($type);
    }

    /**
     *  Return the package types enabled in settings
     */
    public static function enabled() : array
    {
        return array_merge(
            RPM_REPO == 'true' ? ['rpm'] : [],
            DEB_REPO == 'true' ? ['deb'] : [],
            ARCH_REPO == 'true' ? ['arch'] : [],
            ALPINE_REPO == 'true' ? ['alpine'] : [],
            OPENBSD_REPO == 'true' ? ['openbsd'] : []
        );
    }

    /**
     *  Check that the package type is enabled in settings
     */
    public static function checkEnabled(string $type) : void
    {
        if (!in_array($type, self::enabled())) {
            throw new Exception($type . ' repositories are disabled in settings');
        }
    }
}
