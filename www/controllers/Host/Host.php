<?php

namespace Controllers\Host;

use Controllers\User\Permission\Host as HostPermission;
use Controllers\Host\Request as HostRequest;
use Controllers\Group\Host as HostGroup;
use Controllers\Layout\ContainerReload;
use Controllers\Filesystem\Directory;
use Controllers\Utils\Validate;
use JsonException;
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
        try {
            $settings = json_decode($this->model->getSettings()['settings'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Failed to decode settings: ' . $e->getMessage());
        }

        // Complete missing keys with default values
        $settings['compliance']['thresholds']['count'] = $settings['compliance']['thresholds']['count'] ?? 1;
        $settings['compliance']['thresholds']['days'] = $settings['compliance']['thresholds']['days'] ?? 30;
        $settings['compliance']['thresholds']['security-update'] = $settings['compliance']['thresholds']['security-update'] ?? true;
        $settings['compliance']['thresholds']['reboot-required'] = $settings['compliance']['thresholds']['reboot-required'] ?? true;
        $settings['compliance']['exclusions']['hosts'] = $settings['compliance']['exclusions']['hosts'] ?? [];
        $settings['compliance']['exclusions']['packages'] = $settings['compliance']['exclusions']['packages'] ?? [];

        return $settings;
    }

    /**
     *  Edit the display settings on the hosts page
     */
    public function setSettings(array $settings): void
    {
        if (!HostPermission::allowedAction('edit-settings')) {
            throw new Exception('You are not allowed to perform this action');
        }

        // Check the structure before accessing nested settings
        if (!is_array($settings['compliance'] ?? null)
            or !is_array($settings['compliance']['thresholds'] ?? null)
            or !is_array($settings['compliance']['exclusions'] ?? [])) {
            throw new Exception('Invalid compliance settings');
        }

        // Reject unknown keys at every settings level
        if (array_diff(array_keys($settings), ['compliance'])
            or array_diff(array_keys($settings['compliance']), ['thresholds', 'exclusions'])
            or array_diff(array_keys($settings['compliance']['thresholds']), ['count', 'days', 'security-update', 'reboot-required'])
            or array_diff(array_keys($settings['compliance']['exclusions'] ?? []), ['hosts', 'packages'])) {
            throw new Exception('Unknown settings keys');
        }

        $thresholds = $settings['compliance']['thresholds'];
        $exclusions = $settings['compliance']['exclusions'] ?? [];

        // Validate positive integer thresholds and normalize form strings
        foreach (['count', 'days'] as $key) {
            $value = filter_var($thresholds[$key] ?? null, FILTER_VALIDATE_INT);

            if ($value === false or $value <= 0) {
                throw new Exception('Threshold must be an integer greater than 0');
            }

            $thresholds[$key] = $value;
        }

        // Accept only binary flags and store them as integers
        foreach (['security-update', 'reboot-required'] as $key) {
            if (!in_array($thresholds[$key] ?? null, [0, 1, '0', '1', false, true], true)) {
                throw new Exception('Invalid value for compliance ' . $key . ' setting');
            }

            $thresholds[$key] = (int) $thresholds[$key];
        }

        // Default omitted selections to empty arrays
        foreach (['hosts', 'packages'] as $key) {
            $exclusions[$key] = $exclusions[$key] ?? [];

            if (!is_array($exclusions[$key])) {
                throw new Exception('Compliance exclusions must be arrays');
            }
        }

        // Allow hostname patterns with dots and wildcard characters
        foreach ($exclusions['hosts'] as $host) {
            if (!is_string($host) or !preg_match('/^[a-zA-Z0-9_.*-]+$/D', $host)) {
                throw new Exception('Host contains invalid characters');
            }
        }

        // Validate package names while allowing wildcard characters
        foreach ($exclusions['packages'] as $package) {
            if (!is_string($package) or !Validate::alphaNumericHyphen(str_replace('*', '', $package))) {
                throw new Exception('Package contains invalid characters');
            }
        }

        // Keep normalized values and reindex exclusions as JSON lists
        $settings['compliance'] = [
            'thresholds' => $thresholds,
            'exclusions' => [
                'hosts' => array_values($exclusions['hosts']),
                'packages' => array_values($exclusions['packages'])
            ]
        ];

        // Encode the complete settings document for the single JSON column
        try {
            $this->model->setSettings(json_encode($settings, JSON_THROW_ON_ERROR));
        } catch (JsonException $e) {
            throw new Exception('Failed to encode settings: ' . $e->getMessage());
        }
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
}
