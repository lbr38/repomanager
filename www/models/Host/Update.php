<?php

namespace Models\Host;

use Exception;

class Update extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('hosts');
    }

    /**
     *  Update hostname in database
     */
    public function updateHostname(int $id, string $hostname) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Hostname = :hostname WHERE Id = :id");
            $stmt->bindValue(':hostname', $hostname);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update OS in database
     */
    public function updateOS(int $id, string $os) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os = :os WHERE Id = :id");
            $stmt->bindValue(':os', $os);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update OS version in database
     */
    public function updateOsVersion(int $id, string $osVersion) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os_version = :os_version WHERE Id = :id");
            $stmt->bindValue(':os_version', $osVersion);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update OS family in database
     */
    public function updateOsFamily(int $id, string $osFamily) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Os_family = :os_family WHERE Id = :id");
            $stmt->bindValue(':os_family', $osFamily);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update virtualization type in database
     */
    public function updateType(int $id, string $type) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Type = :type WHERE Id = :id");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update CPU in database
     */
    public function updateCpu(int $id, string $cpu) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Cpu = :cpu WHERE Id = :id");
            $stmt->bindValue(':cpu', $cpu);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update RAM in database
     */
    public function updateRam(int $id, string $ram) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Ram = :ram WHERE Id = :id");
            $stmt->bindValue(':ram', $ram);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update kernel version in database
     */
    public function updateKernel(int $id, string $kernel) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Kernel = :kernel WHERE Id = :id");
            $stmt->bindValue(':kernel', $kernel);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update arch in database
     */
    public function updateArch(int $id, string $arch) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Arch = :arch WHERE Id = :id");
            $stmt->bindValue(':arch', $arch);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update profile in database
     */
    public function updateProfile(int $id, string $profile) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Profile = :profile WHERE Id = :id");
            $stmt->bindValue(':profile', $profile);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update environment in database
     */
    public function updateEnv(int $id, string $env) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Env = :env WHERE Id = :id");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update agent status in database
     */
    public function updateAgentStatus(int $id, string $status) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Online_status = :onlineStatus, Online_status_date = :onlineStatusDate, Online_status_time = :OnlineStatusTime WHERE Id = :id");
            $stmt->bindValue(':onlineStatus', $status);
            $stmt->bindValue(':onlineStatusDate', date('Y-m-d'));
            $stmt->bindValue(':OnlineStatusTime', date('H:i:s'));
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update linupdate version in database
     */
    public function updateLinupdateVersion(int $id, string $version) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Linupdate_version = :version WHERE Id = :id");
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update host's reboot required status in database
     */
    public function updateRebootRequired(int $id, string $status) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Reboot_required = :reboot WHERE Id = :id");
            $stmt->bindValue(':reboot', $status);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update host's uptime in database
     */
    public function updateUptime(int $id, float $uptime) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE hosts SET Uptime = :uptime WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':uptime', $uptime);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
