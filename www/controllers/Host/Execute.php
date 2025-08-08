<?php

namespace Controllers\Host;

use Exception;

class Execute extends \Controllers\Host
{
    private $hostRequestController;

    public function __construct()
    {
        parent::__construct();
        $this->hostRequestController = new \Controllers\Host\Request();
    }

    /**
     *  Request one or more host(s) to execute an action
     */
    public function execute(array $hosts, string $action) : string
    {
        $handledHosts = [];

        /**
         *  Only admins should be able to perform actions
         */
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $validActions = ['reset', 'delete', 'request-general-infos', 'request-packages-infos'];

        /**
         *  Check if the action to execute is valid
         */
        if (!in_array($action, $validActions)) {
            throw new Exception('Action to execute is invalid');
        }

        /**
         *  First check that hosts Id are valid and exist
         */
        foreach ($hosts as $hostId) {
            if (!is_numeric($hostId)) {
                throw new Exception('Invalid host Id: ' . $hostId);
            }

            if (!$this->existsId($hostId)) {
                throw new Exception('Host with Id ' . $hostId . ' does not exist');
            }
        }

        foreach ($hosts as $hostId) {
            $hostId = \Controllers\Common::validateData($hostId);

            /**
             *  Retrieve the IP and hostname of the host to be processed
             */
            $ip       = $this->getIpById($hostId);
            $hostname = $this->getHostnameById($hostId);

            /**
             *  If the retrieved ip is empty, we move on to the next host
             */
            if (empty($ip)) {
                continue;
            }

            /**
             *  Case where the requested action is a reset
             */
            if ($action == 'reset') {
                /**
                 *  Reset host data in database
                 */
                $this->reset($hostId);

                /**
                 *  Delete host's dedicated database
                 */
                if (file_exists(HOSTS_DIR . '/' . $hostId . '/properties.db')) {
                    if (!unlink(HOSTS_DIR . '/' . $hostId . '/properties.db')) {
                        throw new Exception('Could not reset ' . $hostname . ' database');
                    }
                }
            }

            /**
             *  Case where the requested action is a delete
             */
            if ($action == 'delete') {
                $this->delete($hostId);
            }

            /**
             *  Case where the requested action is a general status update
             */
            if ($action == 'request-general-infos') {
                /**
                 *  Add a new websocket request in the database
                 */
                $this->hostRequestController->new($hostId, 'request-general-infos');
            }

            /**
             *  Case where the requested action is a packages status update
             */
            if ($action == 'request-packages-infos') {
                /**
                 *  Add a new websocket request in the database
                 */
                $this->hostRequestController->new($hostId, 'request-packages-infos');
            }

            /**
             *  If the host has a hostname, we push it in the array, otherwise we push only its ip
             */
            if (!empty($hostname)) {
                $handledHosts[] = array('ip' => $ip, 'hostname' => $hostname);
            } else {
                $handledHosts[] = array('ip' => $ip);
            }
        }

        /**
         *  Generate a confirmation message with the name/ip of the hosts on which the action has been performed
         */
        if ($action == 'reset') {
            $message = 'Following hosts have been reseted:';
            $this->layoutContainerReloadController->reload('hosts/overview');
            $this->layoutContainerReloadController->reload('hosts/list');
            $this->layoutContainerReloadController->reload('host/summary');
            $this->layoutContainerReloadController->reload('host/packages');
            $this->layoutContainerReloadController->reload('host/history');
        }

        if ($action == 'delete') {
            $message = 'Following hosts have been deleted:';
            $this->layoutContainerReloadController->reload('hosts/overview');
            $this->layoutContainerReloadController->reload('hosts/list');
        }

        if ($action == 'request-all-packages-update') {
            $message = 'Requesting packages update to the following hosts:';
            $this->layoutContainerReloadController->reload('host/requests');
        }

        if ($action == 'request-general-infos') {
            $message = 'Requesting general informations to the following hosts:';
            $this->layoutContainerReloadController->reload('host/requests');
        }

        if ($action == 'request-packages-infos') {
            $message = 'Requesting packages informations to the following hosts:';
            $this->layoutContainerReloadController->reload('host/requests');
        }

        $message .= '<div class="grid grid-2 column-gap-10 row-gap-10 margin-top-5">';

        /**
         *  Print the hostname and ip of the hosts on which the action has been performed
         *  Do not print more than 10 hosts, print +X more if there are more than 10 hosts
         */
        $count = 1;
        foreach ($handledHosts as $host) {
            if ($count > 10) {
                $message .= '<p><b>+' . (count($hosts) - 10) . ' more</b></p>';
                break;
            }

            $message .= '<span class="label-white">' . $host['hostname'] . ' (' . $host['ip'] . ')</span> ';
            $count++;
        }

        $message .= '</div>';

        return $message;
    }

    /**
     *  Update selected available packages on a host
     */
    public function updateSelectedAvailablePackages(int $hostId, array $packages)
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to execute this action');
        }

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
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to execute this action');
        }

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
