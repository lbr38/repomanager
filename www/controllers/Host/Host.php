<?php

namespace Controllers\Host;

use Controllers\User\Permission\Host as HostPermission;
use Controllers\Host\Package\Package as HostPackage;
use Controllers\Host\Package\Event as PackageEvent;
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
    public function setSettings(int $complianceThresholdCount, int $complianceThresholdDays, int $complianceRebootRequired): void
    {
        if (!HostPermission::allowedAction('edit-settings')) {
            throw new Exception('You are not allowed to perform this action');
        }

        if (!is_numeric($complianceThresholdCount) or !is_numeric($complianceThresholdDays) or !is_numeric($complianceRebootRequired)) {
            throw new Exception('Setting must be numeric');
        }

        // Value must be greater than 0
        if ($complianceThresholdCount <= 0 or $complianceThresholdDays <= 0) {
            throw new Exception('Value must be greater than 0');
        }

        if ($complianceRebootRequired !== 0 and $complianceRebootRequired !== 1) {
            throw new Exception('Invalid value for compliance reboot required setting');
        }

        $this->model->setSettings($complianceThresholdCount, $complianceThresholdDays, $complianceRebootRequired);
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
    public function addHostsIdToGroup(int $groupId, array $hostsId = []): void
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

    /**
     *  Return an array with the compliance status of a host
     */
    public function getCompliance(int $hostId): array
    {
        $hostPackageController = new HostPackage($hostId);
        $hostPackageEventController = new PackageEvent($hostId);
        $host = $this->get($hostId);
        $settings = $this->getSettings();

        $compliant = true;
        $reason = '';

        // Retrieve the total number of available packages
        $available = count($hostPackageController->getAvailable());

        // Retrieve the date of the last package upgrade event
        $latestUpdate = $hostPackageEventController->getLastPackageUpgradeEvent()['Date'] ?? null;

        // Calculate the threshold date based on the compliance threshold in days
        $thresholdDate = strtotime('-' . $settings['compliance_threshold_days'] . ' days');

        // The host is not compliant if the available updates count is >= threshold
        if ($available >= $settings['compliance_threshold_count']) {
            $compliant = false;
            $reason = 'Pending updates count (' . $available . ') is greater than or equal to the threshold (' . $settings['compliance_threshold_count'] . ')';
        }

        if (!$latestUpdate) {
            $compliant = false;
            $reason = 'No package update has been performed yet';
        }

        // The host is not compliant if the latest update is older than the compliance threshold defined in settings (in days)
        if ($latestUpdate and $thresholdDate and strtotime($latestUpdate) < $thresholdDate) {
            $compliant = false;
            $reason = 'Latest update date (' . $latestUpdate . ') is older than the compliance threshold (' . $settings['compliance_threshold_days'] . ' days)';
        }

        // Optional rule: host is not compliant when a reboot is required
        $rebootRequired = $host['Reboot_required'] ?? 'false';
        if ((int) $settings['compliance_reboot_required'] === 1 and ($rebootRequired === 'true')) {
            $compliant = false;
            $reason = 'Host requires a reboot';
        }

        return [
            'compliant' => $compliant,
            'reason' => $reason,
            'available_updates_count' => $available,
            'latest_update' => $latestUpdate,
            'reboot_required' => $rebootRequired
        ];
    }
}
