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
     */
    public function getByPackage(string $name, string $version = '', bool $strictName = false, bool $strictVersion = false): array
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
     *  Return all up-to-date hosts
     */
    public function getUpToDate(): array
    {
        $data = [];

        // Get all hosts
        $hosts = $this->get();

        // Get settings
        $settings = $this->hostController->getSettings();

        foreach ($hosts as $host) {
            $hostPackageController = new HostPackage($host['Id']);
            $available = $hostPackageController->getAvailable();

            // If no available update, the host is up-to-date, or if available updates count is not greater than the maximum number of available updates allowed in settings
            if (empty($available) or count($available) <= $settings['pkgs_count_considered_outdated']) {
                $data[] = [
                    'Id' => $host['Id'],
                    'Hostname' => $host['Hostname'],
                    'Ip' => $host['Ip'],
                    'Os' => $host['Os'],
                    'Os_family' => $host['Os_family'],
                    'Available_updates' => [
                        'Total' => count($available),
                        'Packages' => $available
                    ]
                ];
            }
        }

        return $data;
    }

    /**
     *  Return all outdated hosts
     */
    public function getOutdated(): array
    {
        $data = [];

        // Get all hosts
        $hosts = $this->get();

        // Get settings
        $settings = $this->hostController->getSettings();

        foreach ($hosts as $host) {
            $hostPackageController = new HostPackage($host['Id']);
            $available = $hostPackageController->getAvailable();

            // If available updates count is greater than the maximum number of available updates allowed in settings, the host is outdated
            if (count($available) > $settings['pkgs_count_considered_outdated']) {
                $data[] = [
                    'Id' => $host['Id'],
                    'Hostname' => $host['Hostname'],
                    'Ip' => $host['Ip'],
                    'Os' => $host['Os'],
                    'Os_family' => $host['Os_family'],
                    'Available_updates' => [
                        'Total' => count($available),
                        'Packages' => $available
                    ]
                ];
            }
        }

        return $data;
    }
}
