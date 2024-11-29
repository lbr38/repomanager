<?php

namespace Controllers\Host;

use Exception;

class Execute
{
    private $hostRequestController;

    public function __construct()
    {
        $this->hostRequestController = new \Controllers\Host\Request();
    }

    /**
     *  Update selected available packages on a host
     */
    public function updateSelectedAvailablePackages(int $hostId, array $packages)
    {
        /**
         *  Check that $packages is not empty
         */
        if (empty($packages)) {
            throw new Exception('No packages selected');
        }

        /**
         *  Add a new request to the database
         */
        $this->hostRequestController->new($hostId, 'request-packages-update', $packages);
    }

    /**
     *  Install specific packages on host(s) with install params
     */
    // public function installPackages(array $params)
    // {
    //     /**
    //      *  Check that $params is not empty
    //      */
    //     if (empty($params)) {
    //         throw new Exception('No parameters provided');
    //     }

    //     /**
    //      *  Check that $params['hosts'] is not empty
    //      */
    //     if (empty($params['hosts'])) {
    //         throw new Exception('No hosts selected');
    //     }

    //     /**
    //      *  Check that $params['packages'] is not empty
    //      */
    //     if (empty($params['packages'])) {
    //         throw new Exception('No packages selected');
    //     }

    //     /**
    //      *  Add a new request to the database, for each host
    //      */
    //     foreach ($params['hosts'] as $hostId) {
    //         $this->hostRequestController->new($hostId, 'request-packages-installation', $params['packages']);
    //     }
    // }

    /**
     *  Update all or specific packages on host(s) with update params
     */
    public function updatePackages(array $params)
    {
        /**
         *  Check that $params is not empty
         */
        if (empty($params)) {
            throw new Exception('No parameters provided');
        }

        /**
         *  Check that dry-run is not empty
         */

        /**
         *  Check that update-type is not empty
         */
        if (empty($params['update-type'])) {
            throw new Exception('No update type selected');
        }

        /**
         *  Check that ignore-exclusions is not empty
         */
        if (empty($params['ignore-exclusions'])) {
            throw new Exception('No ignore exclusions parameter set');
        }

        /**
         *  Check that full-upgrade is not empty
         */
        if (empty($params['full-upgrade'])) {
            throw new Exception('No full upgrade parameter set');
        }

        /**
         *  Check that keep-config-files is not empty
         */
        if (empty($params['keep-config-files'])) {
            throw new Exception('No keep config files parameter set');
        }

        /**
         *  Check that $params['hosts'] is not empty
         */
        if (empty($params['hosts'])) {
            throw new Exception('No hosts selected');
        }

        /**
         *  If update-type is 'specific', check that $params['packages'] is not empty
         */
        if ($params['update-type'] == 'specific' and empty($params['packages'])) {
            throw new Exception('No packages specified');
        }

        /**
         *  If a list of packages is provided, rebuild the array to match the expected format (each package must be an array with a 'name' key)
         */
        if ($params['update-type'] == 'specific' and !empty($params['packages'])) {
            $packages = [];

            foreach ($params['packages'] as $package) {
                /**
                 *  If package name contains a version, split it
                 */
                if (strpos($package, '=') !== false) {
                    $package = explode('=', $package);
                    $packages[] = ['name' => $package[0], 'target_version' => $package[1]];
                } else {
                    $packages[] = ['name' => $package];
                }
            }
        }

        /**
         *  Add a new request to the database, for each host
         */
        foreach ($params['hosts'] as $hostId) {
            // Case of all packages
            if ($params['update-type'] == 'all') {
                $this->hostRequestController->new(
                    $hostId,
                    'request-all-packages-update',
                    array(
                        'update-params' => array(
                            'dry-run' => $params['dry-run'],
                            'ignore-exclusions' => $params['ignore-exclusions'],
                            'full-upgrade' => $params['full-upgrade'],
                            'keep-config-files' => $params['keep-config-files']
                        )
                    )
                );
            }

            // Case of specific packages
            if ($params['update-type'] == 'specific') {
                $this->hostRequestController->new(
                    $hostId,
                    'request-packages-update',
                    array(
                        'update-params' => array(
                            'dry-run' => $params['dry-run'],
                            'ignore-exclusions' => $params['ignore-exclusions'],
                            'full-upgrade' => $params['full-upgrade'],
                            'keep-config-files' => $params['keep-config-files']
                        ),
                        'packages' => $packages
                    )
                );
            }
        }
    }
}
