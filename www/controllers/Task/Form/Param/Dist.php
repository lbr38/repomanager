<?php

namespace Controllers\Task\Form\Param;

use Exception;
use Controllers\Utils\Validate;

class Dist
{
    /**
     *  Normalize known distribution aliases to their canonical codenames.
     */
    public static function normalize(array $dists) : array
    {
        $normalizedDists = [];
        $aliases = self::aliases();

        foreach ($dists as $dist) {
            $normalizedDists[] = $aliases[strtolower(trim($dist))] ?? $dist;
        }

        return array_values(array_unique($normalizedDists));
    }

    public static function check(array $dists) : void
    {
        if (empty($dists)) {
            throw new Exception('Distribution name must be specified');
        }

        foreach ($dists as $dist) {
            if (!Validate::alphaNumeric($dist, ['-', '_', '.', '/'])) {
                throw new Exception('Distribution name cannot contain special characters except hyphen');
            }
        }
    }

    /**
     *  Build aliases from configured DEB distributions.
     */
    private static function aliases() : array
    {
        $aliases = [];

        foreach (DEB_DISTRIBUTIONS as $distributionName => $distributionDescription) {
            $name = strtolower($distributionName);
            $description = strtolower($distributionDescription);
            $aliases[$name] = $distributionName;
            $aliases[$description] = $distributionName;

            if (preg_match('/^(?<family>[a-z]+)\s+(?<version>[0-9]+(?:\.[0-9]+)?)/i', $distributionDescription, $matches)) {
                $version = strtolower($matches['version']);
                $family = strtolower($matches['family']);
                $aliases[$version] = $distributionName;
                $aliases[$family . ' ' . $version] = $distributionName;
            }
        }

        return $aliases;
    }
}
