<?php

namespace Controllers\Host;

use Controllers\User\Permission\Host as HostPermission;
use Controllers\Host\Request as HostRequest;
use Controllers\Group\Host as HostGroup;
use Controllers\Layout\ContainerReload;
use Controllers\Filesystem\Directory;
use Exception;

class Host
{
    protected $model;
    protected $layoutContainerReloadController;

    public function __construct()
    {
        $this->model = new \Models\Host\Host();
        $this->layoutContainerReloadController = new ContainerReload();
    }

    /**
     *  Return host information from its Id
     */
    public function get(int $id): array
    {
        return $this->model->get($id);
    }

    /**
     *  Return host's IP by its Id
     */
    public function getIp(int $id): string
    {
        return $this->model->getIp($id);
    }

    /**
     *  Return the host's Id from its authId
     */
    public function getIdByAuth(string $authId): int|null
    {
        return $this->model->getIdByAuth($authId);
    }

    /**
     *  Return the host's Id from its hostname
     */
    private function getIdByHostname(string $hostname): int
    {
        $id = $this->model->getIdByHostname($hostname);

        if (empty($id)) {
            throw new Exception('No Id has been found from this hostname');
        }

        return $id;
    }

    /**
     *  Return the hostname of the host by its Id
     */
    public function getHostnameById(int $id): string|null
    {
        $host = $this->get($id);

        return $host['Hostname'] ?? null;
    }

    /**
     *  Return last inserted host Id
     */
    public function getLastInsertRowID(): int
    {
        return $this->model->getLastInsertRowID();
    }

    /**
     *  Return hosts settings
     */
    public function getSettings(): array
    {
        return $this->model->getSettings();
    }

    /**
     *  Edit the display settings on the hosts page
     */
    public function setSettings(int $packagesConsideredOutdated, int $packagesConsideredCritical): void
    {
        if (!HostPermission::allowedAction('edit-settings')) {
            throw new Exception('You are not allowed to perform this action');
        }

        if (!is_numeric($packagesConsideredOutdated) or !is_numeric($packagesConsideredCritical)) {
            throw new Exception('Parameters must be numeric');
        }

        // Parameters must be greater than 0
        if ($packagesConsideredOutdated <= 0 or $packagesConsideredCritical <= 0) {
            throw new Exception('Parameters must be greater than 0');
        }

        $this->model->setSettings($packagesConsideredOutdated, $packagesConsideredCritical);
    }

    /**
     *  Return true if the host Id exists in the database
     */
    public function existsId(int $id): bool
    {
        return $this->model->existsId($id);
    }

    /**
     *  Return true if the hostname exists in the database
     */
    public function existsHostname(string $hostname): bool
    {
        return $this->model->existsHostname($hostname);
    }

    /**
     *  Return true if the Id/token pair is valid
     */
    public function checkIdToken(string $authId, string $token): bool
    {
        if (empty($authId) or empty($token)) {
            return false;
        }

        return $this->model->checkIdToken($authId, $token);
    }

    /**
     *  Add a new host in database
     */
    public function add(string $ip, string $hostname, string $authId, string $token, string $onlineStatus, string $date, string $time): void
    {
        $this->model->add($ip, $hostname, $authId, $token, $onlineStatus, $date, $time);
    }

    /**
     *  Reset a host in the database
     */
    public function reset(int $id): void
    {
        $this->model->reset($id);
    }

    /**
     *  Delete a host from the database by its hostname
     */
    public function deleteByHostname(string $hostname): void
    {
        // Get host Id from its hostname, then delete it
        $id = $this->getIdByHostname($hostname);

        $this->deleteById($id);
    }

    /**
     *  Delete a host from the database
     */
    public function deleteById(int $id): void
    {
        $hostRequestController = new HostRequest();

        // Add a new ws request to disconnect the host
        $hostRequestController->new($id, 'disconnect');

        // Delete host from database
        $this->model->delete($id);

        // Delete host's dedicated database
        if (is_dir(HOSTS_DIR . '/' . $id)) {
            Directory::deleteRecursive(HOSTS_DIR . '/' . $id);
        }

        unset($hostRequestController);
    }

    /**
     *  Add/delete hosts to/from a group
     */
    public function addHostsIdToGroup(array $hostsId = [], int $groupId): void
    {
        $mygroup = new HostGroup();

        if (!empty($hostsId)) {
            foreach ($hostsId as $hostId) {
                if ($this->existsId($hostId) === false) {
                    throw new Exception('Specified host Id #' . $hostId . ' does not exist');
                }

                // Add to group
                $this->model->addToGroup($hostId, $groupId);
            }
        }

        // Retrieve the list of hosts currently in the group to remove those that have not been selected
        $actualHostsMembers = $mygroup->getHostsMembers($groupId);

        // From this list we only get the Id of the currently member hosts
        $actualHostsId = [];

        foreach ($actualHostsMembers as $actualHostsMember) {
            $actualHostsId[] = $actualHostsMember['Id'];
        }

        // Finally, remove all the currently member hosts Id that have not been specified by the user
        foreach ($actualHostsId as $actualHostId) {
            if (!in_array($actualHostId, $hostsId)) {
                $this->model->removeFromGroup($actualHostId, $groupId);
            }
        }
    }
}
