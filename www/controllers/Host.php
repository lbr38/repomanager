<?php

namespace Controllers;

use Exception;

class Host
{
    private $id;
    protected $dedicatedDb;
    protected $model;
    protected $layoutContainerReloadController;

    public function __construct()
    {
        $this->model = new \Models\Host();
        $this->layoutContainerReloadController = new \Controllers\Layout\ContainerReload();
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
     *  Return the host Id from its hostname
     */
    private function getIdByHostname(string $hostname) : int
    {
        $id = $this->model->getIdByHostname($hostname);

        if (empty($id)) {
            throw new Exception('No Id has been found from this hostname');
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
     *  Return the IP of the host by its Id
     */
    public function getIpById(int $id) : string
    {
        return $this->model->getIpById($id);
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
    public function register(string $ip, string $hostname) : array
    {
        /**
         *  Quit if no IP or hostname is provided
         */
        if (empty($ip) or empty($hostname)) {
            throw new Exception('You must provide IP address and hostname.');
        }

        /**
         *  Check if the hostname already exists in the database
         */
        if ($this->model->hostnameExists($hostname) === true) {
            throw new Exception('Host ' . $hostname . ' is already registered.');
        }

        /**
         *  Generate a new authId for this host
         *  This authId will be used to authenticate the host when it will try to connect to the API
         *  It must be unique so loop until we find a unique authId
         */
        $authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  It must be unique so loop until we find a unique authId
         *  We check if an host exist with the same authId
         */
        while (!empty($this->model->getIdByAuth($authId))) {
            $authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));
        }

        /**
         *  Generate a new token for this host
         */
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  Add the host in database
         */
        $this->model->add($ip, $hostname, $authId, $token, 'unknown', date('Y-m-d'), date('H:i:s'));

        /**
         *  Retrieve the Id of the host added in the database
         */
        $id = $this->model->getLastInsertRowID();

        /**
         *  Create a dedicated directory for this host, based on its ID
         */
        if (!mkdir(HOSTS_DIR . '/' . $id, 0770, true)) {
            throw new Exception('The server could not finalize registering.');
        }

        return array('authId' => $authId, 'token' => $token);
    }

    /**
     *  Reset a host in the database
     */
    public function reset(int $id) : void
    {
        $this->model->reset($id);
    }

    /**
     *  Delete a host from the database by its hostname
     */
    public function deleteByHostname(string $hostname) : void
    {
        $id = $this->model->getIdByHostname($hostname);

        if (empty($id)) {
            throw new Exception('Unknown hostname ' . $hostname);
        }

        // Get host Id from its hostname, then delete it
        $this->deleteById($id);
    }

    /**
     *  Delete a host from the database
     */
    public function deleteById(int $id) : void
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
     *  Return hosts that have the specified package
     */
    public function getHostsWithPackage(array $hosts, string $name, string|null $version, bool $strictName, bool $strictVersion) : array
    {
        $results = array();

        if (empty($hosts)) {
            throw new Exception('No host specified');
        }

        if (!is_array($hosts)) {
            throw new Exception('Invalid host Ids format');
        }

        /**
         *  Check if the package name is valid
         */
        if (!\Controllers\Common::isAlphanumDash($name, array('*'))) {
            throw new Exception('Package name contains invalid characters');
        }

        /**
         *  For each host, search for the package in the host's database and return the result
         */
        foreach ($hosts as $id) {
            $hostPackageController = new \Controllers\Host\Package\Package($id);
            $results[$id] = $hostPackageController->searchPackage($name, $version, $strictName, $strictVersion);
        }

        unset($hostPackageController);

        return $results;
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
