<?php

namespace Controllers\Host;

use Controllers\Host\Package\Package as HostPackage;
use Controllers\Utils\Validate;
use Exception;

class Listing
{
    private $model;
    private $hostController;

    public function __construct()
    {
        $this->hostController = new Host();
        $this->model = new \Models\Host\Listing();
    }

    /**
     *  Return all hosts
     */
    public function get(): array
    {
        return $this->model->get();
    }

    /**
     *  Return hosts with the specified OS and OS version (optional)
     */
    public function getByOs(string $os, string $osVersion = ''): array
    {
        return $this->model->getByOs(Validate::string($os), Validate::string($osVersion));
    }

    /**
     *  Return hosts with the specified kernel
     */
    public function getByKernel(string $kernel): array
    {
        return $this->model->getByKernel(Validate::string($kernel));
    }

    /**
     *  Return hosts with the specified architecture
     */
    public function getByArch(string $arch): array
    {
        return $this->model->getByArch(Validate::string($arch));
    }

    /**
     *  Return hosts with the specified profile
     */
    public function getByProfile(string $profile): array
    {
        return $this->model->getByProfile(Validate::string($profile));
    }

    /**
     *  Return hosts with the specified environment
     */
    public function getByEnvironment(string $environment): array
    {
        return $this->model->getByEnvironment(Validate::string($environment));
    }

    /**
     *  Return all hosts by group name
     */
    public function getByGroup(string $group): array
    {
        return $this->model->getByGroup(Validate::string($group));
    }

    /**
     *  Return hosts that have the specified package
     *  If $absent is true, return hosts that do NOT have the specified package instead
     */
    public function getByPackage(string $name, string $version = '', bool $strictName = false, bool $strictVersion = false, bool $absent = false): array
    {
        $data = [];

        // Get all hosts
        $hosts = $this->get();

        // Check if the package name is valid
        if (!Validate::alphaNumericHyphen($name, ['*'])) {
            throw new Exception('Package name contains invalid characters');
        }

        $version = Validate::string($version);

        // For each host, search for the package in the host's database and return the result
        foreach ($hosts as $host) {
            $hostPackageController = new HostPackage($host['Id']);
            $results = $hostPackageController->searchPackage($name, $version, $strictName, $strictVersion);

            // If looking for hosts on which the package is absent
            if ($absent) {
                // If the host has the specified package, continue to the next host
                if (!empty($results)) {
                    continue;
                }

                $data[] = [
                    'id' => $host['Id'],
                    'hostname' => $host['Hostname']
                ];

                continue;
            }

            // If the host does not have the specified package, continue to the next host
            if (empty($results)) {
                continue;
            }

            $data[] = [
                'id' => $host['Id'],
                'hostname' => $host['Hostname'],
                'packages' => $results
            ];
        }

        unset($hostPackageController);

        return $data;
    }

    /**
     *  Return all OS names and their count
     */
    public function getOs(): array
    {
        return $this->model->getOs();
    }

    /**
     *  Return all kernel names and their count
     */
    public function getKernel(): array
    {
        return $this->model->getKernel();
    }

    /**
     *  Return all arch names and their count
     */
    public function getArch(): array
    {
        return $this->model->getArch();
    }

    /**
     *  Return all profile names and their count
     */
    public function getProfile(): array
    {
        return $this->model->getProfile();
    }

    /**
     *  Return all environment names and their count
     */
    public function getEnvironment(): array
    {
        return $this->model->getEnvironment();
    }

    /**
     *  Return all hosts that require reboot
     */
    public function getRebootRequired(): array
    {
        return $this->model->getRebootRequired();
    }

    /**
     *  Return all agent status and their count
     */
    public function getAgentStatus() : array
    {
        return $this->model->getAgentStatus();
    }

    /**
     *  Return all agent version and their count
     */
    public function getAgentVersion(): array
    {
        return $this->model->getAgentVersion();
    }

    /**
     *  Return all up-to-date hosts (0 available updates)
     *  With the list of their available updates if $listPackages is true
     */
    public function getUpToDate(bool $listPackages = false): array
    {
        return $this->getByAvailableUpdates(false, $listPackages);
    }

    /**
     *  Return all outdated hosts (at least 1 available update)
     *  With the list of their available updates if $listPackages is true
     */
    public function getOutdated(bool $listPackages = false): array
    {
        return $this->getByAvailableUpdates(true, $listPackages);
    }

    /**
     *  Return all compliant hosts (all compliance criteria)
     *  With the list of their available updates if $listPackages is true
     */
    public function getCompliant(bool $listPackages = false): array
    {
        return $this->getByCompliance(true, $listPackages);
    }

    /**
     *  Return all non-compliant hosts (all compliance criteria)
     *  With the list of their available updates if $listPackages is true
     */
    public function getNonCompliant(bool $listPackages = false): array
    {
        return $this->getByCompliance(false, $listPackages);
    }

    /**
     *  Return hosts filtered by whether they have available updates
     *  $hasAvailable = true: at least 1 available update
     *  $hasAvailable = false: 0 available updates
     */
    private function getByAvailableUpdates(bool $hasAvailable, bool $listPackages = false): array
    {
        $data = [];

        foreach ($this->get() as $host) {
            $hostPackageController = new HostPackage($host['Id']);
            $packages = $hostPackageController->getAvailable();
            $availableCount = count($packages);

            if ($hasAvailable && $availableCount === 0) {
                continue;
            }
            if (!$hasAvailable && $availableCount > 0) {
                continue;
            }

            $array = [
                'Id' => $host['Id'],
                'Hostname' => $host['Hostname'],
                'Ip' => $host['Ip'],
                'Os' => $host['Os'],
                'Os_version' => $host['Os_version'],
                'Os_family' => $host['Os_family'],
                'Kernel' => $host['Kernel'],
                'Arch' => $host['Arch'],
                'Type' => $host['Type'],
                'Profile' => $host['Profile'],
                'Env' => $host['Env']
            ];

            // If the host has available updates, add the count of available updates
            if ($hasAvailable) {
                $array['Available_updates']['Total'] = $availableCount;

                // If the user requested to list the available updates, get the list of packages
                if ($listPackages) {
                    $array['Available_updates']['Packages'] = $packages;
                }
            }

            $data[] = $array;
        }

        return $data;
    }

    /**
     *  Return hosts filtered by compliance status
     */
    private function getByCompliance(bool $compliant, bool $listPackages = false): array
    {
        $data = [];

        foreach ($this->get() as $host) {
            $compliance = $this->hostController->getCompliance($host['Id']);

            // If the host's compliance status does not match the requested compliance status, continue to the next host
            if ($compliance['compliant'] !== $compliant) {
                continue;
            }

            $array = [
                'Id' => $host['Id'],
                'Hostname' => $host['Hostname'],
                'Ip' => $host['Ip'],
                'Os' => $host['Os'],
                'Os_version' => $host['Os_version'],
                'Os_family' => $host['Os_family'],
                'Kernel' => $host['Kernel'],
                'Arch' => $host['Arch'],
                'Type' => $host['Type'],
                'Profile' => $host['Profile'],
                'Env' => $host['Env'],
                'Compliant' => $compliance['compliant'],
                'Reason' => $compliance['reason'],
                'Latest_update' => $compliance['latest_update'],
                'Available_updates' => [
                    'Total' => $compliance['available_updates_count']
                ]
            ];

            // If the user requested to list the available updates, get the list of packages
            if ($listPackages) {
                $hostPackageController = new HostPackage($host['Id']);
                $array['Available_updates']['Packages'] = $hostPackageController->getAvailable();
            }

            $data[] = $array;
        }

        return $data;
    }
}
