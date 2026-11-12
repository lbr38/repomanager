<?php

namespace Controllers\Host;

use Exception;
use JsonException;
use Controllers\Utils\Validate;

class Update
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Host\Update();
    }

    /**
     *  Update the general information and status sent by a host (linupdate agent)
     *  Only the specified values are updated, return the updated values as an array value => label (e.g. 'os_version' => 'OS version')
     */
    public function updateStatus(int $id, array $data): array
    {
        // Value sent by the host => update method and label of the value
        $fields = [
            'hostname'        => ['updateHostname', 'Hostname'],
            'os'              => ['updateOS', 'OS'],
            'os_version'      => ['updateOsVersion', 'OS version'],
            'os_family'       => ['updateOsFamily', 'OS family'],
            'type'            => ['updateType', 'Virtualization type'],
            'cpu'             => ['updateCpu', 'CPU'],
            'ram'             => ['updateRam', 'RAM'],
            'network'         => ['updateNetwork', 'Network'],
            'public_ip'       => ['updatePublicIp', 'Public IP'],
            'kernel'          => ['updateKernel', 'Kernel'],
            'arch'            => ['updateArch', 'Arch'],
            'profile'         => ['updateProfile', 'Profile'],
            'env'             => ['updateEnv', 'Environment'],
            'agent_status'    => ['updateAgentStatus', 'Agent status'],
            'agent_version'   => ['updateAgentVersion', 'Agent version'],
            'reboot_required' => ['updateRebootRequired', 'Reboot status'],
            'uptime'          => ['updateUptime', 'Uptime']
        ];

        $updated = [];

        foreach ($fields as $key => [$method, $label]) {
            if (empty($data[$key])) {
                continue;
            }

            // A value of an invalid type raises a TypeError
            try {
                $this->$method($id, $data[$key]);
            } catch (Exception | \TypeError $e) {
                throw new Exception($label . ' update has failed.');
            }

            $updated[$key] = $label;
        }

        if (isset($updated['network'])) {
            // Replace the registered IP by the host's main IP if the host does not own the registered IP anymore
            try {
                if ($this->updateIpFromNetwork($id, $data['network'])) {
                    $updated['ip'] = 'IP';
                }
            } catch (Exception $e) {
                throw new Exception('IP update has failed.');
            }

            // A host not declared as behind a NAT has no public IP (a host behind a NAT which could not retrieve it keeps the last known one)
            if (empty($data['public_ip']) and ($data['behind_nat'] ?? '') !== 'true') {
                $this->model->updatePublicIp($id, '');
            }
        }

        return $updated;
    }

    /**
     *  Replace the registered IP of the host by its main IP when the registered IP is no longer one of its addresses
     *  Return true if the IP has been replaced
     */
    private function updateIpFromNetwork(int $id, array $network): bool
    {
        $addresses = [];
        $mainIp = '';

        foreach ($network as $intData) {
            // Only hosts sending all the addresses of their interfaces can be compared reliably
            if (!is_array($intData) or !isset($intData['addresses']) or !is_array($intData['addresses'])) {
                continue;
            }

            foreach ($intData['addresses'] as $address) {
                // Remove the IPv6 zone index (e.g. fe80::1%eth0)
                $address = is_string($address) ? explode('%', $address)[0] : '';

                if (filter_var($address, FILTER_VALIDATE_IP) !== false) {
                    $addresses[] = inet_pton($address);
                }
            }

            if (!empty($intData['main']) and filter_var($intData['ipv4'] ?? '', FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
                $mainIp = $intData['ipv4'];
            }
        }

        // Without a main IP or the host addresses, there is nothing to compare or to replace with
        if (empty($mainIp) or empty($addresses)) {
            return false;
        }

        $registeredIp = (new \Controllers\Host\Host())->getIp($id);

        // Keep the registered IP while the host still owns it
        if (filter_var($registeredIp, FILTER_VALIDATE_IP) !== false and in_array(inet_pton($registeredIp), $addresses, true)) {
            return false;
        }

        $this->model->updateIp($id, $mainIp);

        return true;
    }

    /**
     *  Update hostname in database
     */
    public function updateHostname(int $id, string $hostname) : void
    {
        $this->model->updateHostname($id, Validate::string($hostname));
    }

    /**
     *  Update OS in database
     */
    public function updateOS(int $id, string $os) : void
    {
        $this->model->updateOS($id, Validate::string($os));
    }

    /**
     *  Update OS version in database
     */
    public function updateOsVersion(int $id, string $osVersion) : void
    {
        $this->model->updateOsVersion($id, Validate::string($osVersion));
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(int $id, string $osFamily) : void
    {
        $this->model->updateOsFamily($id, Validate::string($osFamily));
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(int $id, string $virtType) : void
    {
        $this->model->updateType($id, Validate::string($virtType));
    }

    /**
     *  Update CPU in database
     */
    public function updateCpu(int $id, string $cpu) : void
    {
        $this->model->updateCpu($id, Validate::string($cpu));
    }

    /**
     *  Update RAM in database
     */
    public function updateRam(int $id, string $ram) : void
    {
        $this->model->updateRam($id, Validate::string($ram));
    }

    /**
     *  Update network in database
     */
    public function updateNetwork(int $id, array $network) : void
    {
        $data = [];

        // Validate network data
        foreach ($network as $int => $intData) {
            $data[$int]['ipv4'] = Validate::string($intData['ipv4'] ?? '');
            $data[$int]['ipv6'] = Validate::string($intData['ipv6'] ?? '');
            $data[$int]['mac'] = Validate::string($intData['mac'] ?? '');

            // All the addresses of the interface (only sent by recent linupdate versions)
            if (isset($intData['addresses']) and is_array($intData['addresses'])) {
                $data[$int]['addresses'] = array_map(fn ($address) => Validate::string($address), array_values($intData['addresses']));
            }
        }

        // Encode to JSON for database storage
        try {
            $data = json_encode($data, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('could not encode network data to JSON');
        }

        $this->model->updateNetwork($id, $data);
    }

    /**
     *  Update host's public IP (sent by hosts behind a NAT) in database
     */
    public function updatePublicIp(int $id, string $ip) : void
    {
        $ip = trim($ip);

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            throw new Exception('Public IP is invalid');
        }

        $this->model->updatePublicIp($id, $ip);
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(int $id, string $kernel) : void
    {
        $this->model->updateKernel($id, Validate::string($kernel));
    }

    /**
     *  Update arch in database
     */
    public function updateArch(int $id, string $arch) : void
    {
        $this->model->updateArch($id, Validate::string($arch));
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(int $id, string $profile) : void
    {
        $this->model->updateProfile($id, Validate::string($profile));
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(int $id, string $env) : void
    {
        $this->model->updateEnv($id, Validate::string($env));
    }

    /**
     *  Update agent status in database
     */
    public function updateAgentStatus(int $id, string $status) : void
    {
        if (!in_array($status, ['running', 'stopped', 'disabled'])) {
            throw new Exception('Agent status is invalid');
        }

        $this->model->updateAgentStatus($id, $status);
    }

    /**
     *  Update host's agent version in database
     */
    public function updateAgentVersion(int $id, string $version) : void
    {
        $this->model->updateAgentVersion($id, Validate::string($version));
    }

    /**
     *  Update host's reboot required status in database
     */
    public function updateRebootRequired(int $id, string $status) : void
    {
        if (!in_array($status, ['true', 'false'])) {
            throw new Exception('Reboot status is invalid');
        }

        $this->model->updateRebootRequired($id, $status);
    }

    /**
     *  Update host's uptime in database
     */
    public function updateUptime(int $id, float $uptime) : void
    {
        if (!is_numeric($uptime)) {
            throw new Exception('Uptime must be a timestamp');
        }

        $this->model->updateUptime($id, $uptime);
    }
}
