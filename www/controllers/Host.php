<?php

namespace Controllers;

use Exception;
use Datetime;

class Host
{
    protected $dedicatedDb;
    private $model;
    private $layoutContainerReloadController;
    private $id;
    private $idArray = array();
    private $ip;
    private $hostname;
    private $os;
    private $os_version;
    private $os_family;
    private $type;
    private $kernel;
    private $arch;
    private $profile;
    private $env;
    private $authId;
    private $token;
    private $onlineStatus;

    public function __construct()
    {
        $this->model = new \Models\Host();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();
    }

    public function setId(string $id)
    {
        $this->id = \Controllers\Common::validateData($id);
    }

    public function setIp(string $ip)
    {
        $this->ip = \Controllers\Common::validateData($ip);
    }

    public function setHostname(string $hostname)
    {
        $this->hostname = \Controllers\Common::validateData($hostname);
    }

    public function setOS(string $os)
    {
        $this->os = \Controllers\Common::validateData($os);
    }

    public function setOsVersion(string $os_version)
    {
        $this->os_version = \Controllers\Common::validateData($os_version);
    }

    public function setOsFamily(string $os_family)
    {
        $this->os_family = \Controllers\Common::validateData($os_family);
    }

    public function setType(string $type)
    {
        $this->type = \Controllers\Common::validateData($type);
    }

    public function setKernel(string $kernel)
    {
        $this->kernel = \Controllers\Common::validateData($kernel);
    }

    public function setArch(string $arch)
    {
        $this->arch = \Controllers\Common::validateData($arch);
    }

    public function setProfile(string $profile)
    {
        $this->profile = \Controllers\Common::validateData($profile);
    }

    public function setEnv(string $env)
    {
        $this->env = \Controllers\Common::validateData($env);
    }

    public function setAuthId(string $authId)
    {
        $this->authId = \Controllers\Common::validateData($authId);
    }

    public function setToken(string $token)
    {
        $this->token = \Controllers\Common::validateData($token);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getAuthId()
    {
        return $this->authId;
    }

    public function getToken()
    {
        return $this->token;
    }

    /**
     *  Return hosts settings
     */
    public function getSettings() : array
    {
        return $this->model->getSettings();
    }

    /**
     *  Return the host Id from its authId
     */
    public function getIdByAuth(string $authId) : int
    {
        $id = $this->model->getIdByAuth($authId);

        if (empty($id)) {
            throw new Exception("No host Id has been found from this authId identifier");
        }

        return $id;
    }

    /**
     *  Return all host information from its Id
     */
    public function getAll(string $id) : array
    {
        return $this->model->getAll($id);
    }

    /**
     *  Return the hostname of the host by its Id
     */
    public function getHostnameById(int $id) : string
    {
        return $this->model->getHostnameById($id);
    }

    /**
     *  Return hosts that have the specified kernel
     */
    public function getHostWithKernel(string $kernel) : array
    {
        return $this->model->getHostWithKernel($kernel);
    }

    /**
     *  Return hosts that have the specified profile
     */
    public function getHostWithProfile(string $profile) : array
    {
        return $this->model->getHostWithProfile($profile);
    }

    /**
     *  Edit the display settings on the hosts page
     */
    public function setSettings(string $packagesConsideredOutdated, string $packagesConsideredCritical) : void
    {
        if (!is_numeric($packagesConsideredOutdated) or !is_numeric($packagesConsideredCritical)) {
            throw new Exception('Parameters must be numeric');
        }

        /**
         *  Parameters must be greater than 0
         */
        if ($packagesConsideredOutdated <= 0 or $packagesConsideredCritical <= 0) {
            throw new Exception('Parameters must be greater than 0');
        }

        $this->model->setSettings($packagesConsideredOutdated, $packagesConsideredCritical);
    }

    /**
     *  Return true if the host Id exists in the database
     */
    public function existsId(int $id) : bool
    {
        return $this->model->existsId($id);
    }

    /**
     *  Return true if the IP exists in the database
     */
    private function ipExists(string $ip) : bool
    {
        return $this->model->ipExists($ip);
    }

    /**
     *  Return true if the Id/token pair is valid
     */
    public function checkIdToken(string $authId, string $token) : bool
    {
        if (empty($authId) or empty($token)) {
            return false;
        }

        return $this->model->checkIdToken($authId, $token);
    }

    /**
     *  Return all hosts from a group
     */
    public function listByGroup(string $groupName) : array
    {
        return $this->model->listByGroup($groupName);
    }

    /**
     *  Return all hosts
     */
    public function listAll() : array
    {
        return $this->model->listAll();
    }

    /**
     *  Return all OS names and their count
     */
    public function listCountOS() : array
    {
        return $this->model->listCountOS();
    }

    /**
     *  Return all kernel names and their count
     */
    public function listCountKernel() : array
    {
        return $this->model->listCountKernel();
    }

    /**
     *  Return all arch names and their count
     */
    public function listCountArch() : array
    {
        return $this->model->listCountArch();
    }

    /**
     *  Return all env names and their count
     */
    public function listCountEnv() : array
    {
        return $this->model->listCountEnv();
    }

    /**
     *  Return all profile names and their count
     */
    public function listCountProfile() : array
    {
        return $this->model->listCountProfile();
    }

    /**
     *  Return all agent status and their count
     */
    public function listCountAgentStatus() : array
    {
        return $this->model->listCountAgentStatus();
    }

    /**
     *  Return all agent version and their count
     */
    public function listCountAgentVersion() : array
    {
        return $this->model->listCountAgentVersion();
    }

    /**
     *  Return all hosts that require a reboot
     */
    public function listRebootRequired() : array
    {
        return $this->model->listRebootRequired();
    }

    /**
     *  Return the number of hosts using the specified profile
     */
    public function countByProfile(string $profile) : int
    {
        return $this->model->countByProfile($profile);
    }

    /**
     *  Register a new host in the database
     */
    public function register() : void
    {
        /**
         *  Quit if no IP or hostname is provided
         */
        if (empty($this->ip) or empty($this->hostname)) {
            throw new Exception('You must provide IP address and hostname.');
        }

        /**
         *  Check if the hostname already exists in the database
         */
        if ($this->model->hostnameExists($this->hostname) === true) {
            throw new Exception('Host ' . $this->hostname . ' is already registered.');
        }

        /**
         *  Generate a new authId for this host
         *  This authId will be used to authenticate the host when it will try to connect to the API
         *  It must be unique so loop until we find a unique authId
         */
        $this->authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  It must be unique so loop until we find a unique authId
         *  We check if an host exist with the same authId
         */
        while (!empty($this->model->getIdByAuth($this->authId))) {
            $this->authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));
        }

        /**
         *  Generate a new token for this host
         */
        $this->token = bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  The agent status is set to 'unknown' when we register a new host for the first time
         */
        $this->onlineStatus = 'unknown';

        /**
         *  Add the host in database
         */
        $this->model->add($this->ip, $this->hostname, $this->authId, $this->token, $this->onlineStatus, date('Y-m-d'), date('H:i:s'));

        /**
         *  Retrieve the Id of the host added in the database
         */
        $this->id = $this->model->getLastInsertRowID();

        /**
         *  Create a dedicated directory for this host, based on its ID
         */
        if (!mkdir(HOSTS_DIR . '/' . $this->id, 0770, true)) {
            throw new Exception('The server could not finalize registering.');
        }
    }

    /**
     *  Delete a host from the database
     */
    public function delete(int $id) : void
    {
        $hostRequestController = new \Controllers\Host\Request();

        /**
         *  Delete host from database
         */
        $this->model->delete($id);

        /**
         *  Add a new ws request to disconnect the host
         */
        $hostRequestController->new($id, 'disconnect');

        /**
         *  Delete host's dedicated database
         */
        if (is_dir(HOSTS_DIR . '/' . $id)) {
            \Controllers\Filesystem\Directory::deleteRecursive(HOSTS_DIR . '/' . $id);
        }

        unset($hostRequestController);
    }

    /**
     *  Ask one or more host(s) to execute an action
     */
    public function hostExec(array $hostsId, string $action) : string
    {
        /**
         *  Only admins should be able to perform actions
         */
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to perform this action');
        }

        $hostRequestController = new \Controllers\Host\Request();
        $validActions = ['reset', 'delete', 'request-general-infos', 'request-packages-infos'];

        /**
         *  Check if the action to execute is valid
         */
        if (!in_array($action, $validActions)) {
            throw new Exception('Action to execute is invalid');
        }

        /**
         *  First check that hosts Id are valid
         */
        foreach ($hostsId as $hostId) {
            if (!is_numeric($hostId)) {
                throw new Exception('Invalid host Id: ' . $hostId);
            }
        }

        foreach ($hostsId as $hostId) {
            $hostId = \Controllers\Common::validateData($hostId);

            /**
             *  Retrieve the IP and hostname of the host to be processed
             */
            $hostname = $this->getHostnameById($hostId);
            $ip = $this->model->getIpById($hostId);

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
                $this->model->resetHost($hostId);

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
                $hostRequestController->new($hostId, 'request-general-infos');
            }

            /**
             *  Case where the requested action is a packages status update
             */
            if ($action == 'request-packages-infos') {
                /**
                 *  Add a new websocket request in the database
                 */
                $hostRequestController->new($hostId, 'request-packages-infos');
            }

            /**
             *  If the host has a hostname, we push it in the array, otherwise we push only its ip
             */
            if (!empty($hostname)) {
                $hosts[] = array('ip' => $ip, 'hostname' => $hostname);
            } else {
                $hosts[] = array('ip' => $ip);
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
        foreach ($hosts as $host) {
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
     *  Return hosts that have the specified package
     */
    public function getHostsWithPackage(array $hostsId, string $packageName) : array
    {
        $hosts = array();

        if (empty($hostsId)) {
            throw new Exception('No host specified');
        }

        if (!is_array($hostsId)) {
            throw new Exception('Invalid host Ids format');
        }

        /**
         *  Check if the package name is valid
         */
        if (!\Controllers\Common::isAlphanumDash($packageName, array('*'))) {
            throw new Exception('Package name contains invalid characters');
        }

        /**
         *  For each host, search for the package in the host's database and return the result
         */
        foreach ($hostsId as $id) {
            $hostPackageController = new \Controllers\Host\Package\Package($id);
            $hosts[$id] = $hostPackageController->searchPackage($packageName);
        }

        unset($hostPackageController);

        return $hosts;
    }

    /**
     *  Update hostname in database
     */
    public function updateHostname(string $hostname) : void
    {
        $this->model->updateHostname($this->id, \Controllers\Common::validateData($hostname));
    }

    /**
     *  Update OS in database
     */
    public function updateOS(string $os) : void
    {
        $this->model->updateOS($this->id, \Controllers\Common::validateData($os));
    }

    /**
     *  Update OS version in database
     */
    public function updateOsVersion(string $osVersion) : void
    {
        $this->model->updateOsVersion($this->id, \Controllers\Common::validateData($osVersion));
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(string $osFamily) : void
    {
        $this->model->updateOsFamily($this->id, \Controllers\Common::validateData($osFamily));
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(string $virtType) : void
    {
        $this->model->updateType($this->id, \Controllers\Common::validateData($virtType));
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(string $kernel) : void
    {
        $this->model->updateKernel($this->id, \Controllers\Common::validateData($kernel));
    }

    /**
     *  Update arch in database
     */
    public function updateArch(string $arch) : void
    {
        $this->model->updateArch($this->id, \Controllers\Common::validateData($arch));
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(string $profile) : void
    {
        $this->model->updateProfile($this->id, \Controllers\Common::validateData($profile));
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(string $env) : void
    {
        $this->model->updateEnv($this->id, \Controllers\Common::validateData($env));
    }

    /**
     *  Update agent status in database
     */
    public function updateAgentStatus(string $status) : void
    {
        if ($status != 'running' and $status != 'stopped' and $status != 'disabled') {
            throw new Exception('Agent status is invalid');
        }

        $this->model->updateAgentStatus($this->id, $status);
    }

    /**
     *  Update host's linupdate version in database
     */
    public function updateLinupdateVersion(string $version) : void
    {
        $this->model->updateLinupdateVersion($this->id, \Controllers\Common::validateData($version));
    }

    /**
     *  Update host's reboot required status in database
     */
    public function updateRebootRequired(string $status) : void
    {
        if ($status != 'true' and $status != 'false') {
            throw new Exception('Reboot status is invalid');
        }

        $this->model->updateRebootRequired($this->id, $status);
    }

    /**
     *  Add/delete hosts to/from a group
     */
    public function addHostsIdToGroup(array $hostsId = null, int $groupId) : void
    {
        $mygroup = new \Controllers\Group('host');

        if (!empty($hostsId)) {
            foreach ($hostsId as $hostId) {
                if ($this->existsId($hostId) === false) {
                    throw new Exception('Specified host Id #' . $hostId . ' does not exist');
                }

                // Add to group
                $this->model->addToGroup($hostId, $groupId);
            }
        }

        /**
         *  Retrieve the list of hosts currently in the group to remove those that have not been selected
         */
        $actualHostsMembers = $mygroup->getHostsMembers($groupId);

        /**
         *  From this list we only get the Id of the currently member hosts
         */
        $actualHostsId = array();

        foreach ($actualHostsMembers as $actualHostsMember) {
            $actualHostsId[] = $actualHostsMember['Id'];
        }

        /**
         *  Finally, we remove all the currently member hosts Id that have not been specified by the user
         */
        foreach ($actualHostsId as $actualHostId) {
            if (!in_array($actualHostId, $hostsId)) {
                $this->model->removeFromGroup($actualHostId, $groupId);
            }
        }
    }
}
