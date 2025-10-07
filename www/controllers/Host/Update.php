<?php

namespace Controllers\Host;

use Exception;
use \Controllers\Common;

class Update
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Host\Update();
    }

    /**
     *  Update hostname in database
     */
    public function updateHostname(int $id, string $hostname) : void
    {
        $this->model->updateHostname($id, Common::validateData($hostname));
    }

    /**
     *  Update OS in database
     */
    public function updateOS(int $id, string $os) : void
    {
        $this->model->updateOS($id, Common::validateData($os));
    }

    /**
     *  Update OS version in database
     */
    public function updateOsVersion(int $id, string $osVersion) : void
    {
        $this->model->updateOsVersion($id, Common::validateData($osVersion));
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(int $id, string $osFamily) : void
    {
        $this->model->updateOsFamily($id, Common::validateData($osFamily));
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(int $id, string $virtType) : void
    {
        $this->model->updateType($id, Common::validateData($virtType));
    }

    /**
     *  Update CPU in database
     */
    public function updateCpu(int $id, string $cpu) : void
    {
        $this->model->updateCpu($id, Common::validateData($cpu));
    }

    /**
     *  Update RAM in database
     */
    public function updateRam(int $id, string $ram) : void
    {
        $this->model->updateRam($id, Common::validateData($ram));
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(int $id, string $kernel) : void
    {
        $this->model->updateKernel($id, Common::validateData($kernel));
    }

    /**
     *  Update arch in database
     */
    public function updateArch(int $id, string $arch) : void
    {
        $this->model->updateArch($id, Common::validateData($arch));
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(int $id, string $profile) : void
    {
        $this->model->updateProfile($id, Common::validateData($profile));
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(int $id, string $env) : void
    {
        $this->model->updateEnv($id, Common::validateData($env));
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
     *  Update host's linupdate version in database
     */
    public function updateLinupdateVersion(int $id, string $version) : void
    {
        $this->model->updateLinupdateVersion($id, Common::validateData($version));
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
