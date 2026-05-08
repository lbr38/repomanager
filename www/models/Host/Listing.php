<?php

namespace Models\Host;

use Controllers\Database\Log as DbLog;
use Exception;

class Listing extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('hosts');
    }

    /**
     *  Return all hosts
     */
    public function get(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT * FROM hosts");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return hosts with the specified OS and OS version (optional)
     */
    public function getByOs(string $os, string $osVersion): array
    {
        $data = [];

        try {
            $query = "SELECT * FROM hosts WHERE Os = :os";

            if (!empty($osVersion)) {
                $query .= " AND Os_version = :os_version";
            }

            $query .= " ORDER BY Hostname ASC";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':os', $os);
            $stmt->bindValue(':os_version', $osVersion);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return hosts that have the specified kernel
     */
    public function getByKernel(string $kernel): array
    {
        $data = [];

        try {
            // Case the kernel is empty (hosts which have not sent their information yet)
            if (empty($kernel)) {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Kernel IS NULL ORDER BY Hostname ASC");
            } else {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Kernel = :kernel ORDER BY Hostname ASC");
                $stmt->bindValue(':kernel', $kernel);
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return hosts with the specified architecture
     */
    public function getByArch(string $arch): array
    {
        $data = [];

        try {
            // Case the architecture is empty (hosts which have not sent their information yet)
            if (empty($arch)) {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Arch IS NULL ORDER BY Hostname ASC");
            } else {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Arch = :arch ORDER BY Hostname ASC");
                $stmt->bindValue(':arch', $arch);
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return hosts with the specified profile
     */
    public function getByProfile(string $profile): array
    {
        $data = [];

        try {
            // Case the profile is empty (hosts which have not sent their information yet)
            if (empty($profile)) {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Profile IS NULL ORDER BY Hostname ASC");
            } else {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Profile = :profile ORDER BY Hostname ASC");
                $stmt->bindValue(':profile', $profile);
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return hosts with the specified environment
     */
    public function getByEnvironment(string $environment): array
    {
        $data = [];

        try {
            // Case the environment is empty (hosts which have not sent their information yet)
            if (empty($environment)) {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Env IS NULL ORDER BY Hostname ASC");
            } else {
                $stmt = $this->db->prepare("SELECT * FROM hosts WHERE Env = :environment ORDER BY Hostname ASC");
                $stmt->bindValue(':environment', $environment);
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all hosts by group name
     */
    public function getByGroup(string $group): array
    {
        $data = [];

        try {
            // If the group name is 'Default' (fictitious group) then we display all hosts without a group
            if ($group == 'Default') {
                $result = $this->db->query("SELECT * FROM hosts
                WHERE Id NOT IN (SELECT Id_host FROM group_members)
                ORDER BY hosts.Hostname ASC");
            } else {
                $stmt = $this->db->prepare("SELECT
                hosts.Id,
                hosts.Ip,
                hosts.Hostname,
                hosts.Os,
                hosts.Os_version,
                hosts.Os_family,
                hosts.Type,
                hosts.Kernel,
                hosts.Arch,
                hosts.Profile,
                hosts.Env,
                hosts.Online_status,
                hosts.Online_status_date,
                hosts.Online_status_time,
                hosts.Reboot_required,
                hosts.Linupdate_version
                FROM hosts
                INNER JOIN group_members
                    ON hosts.Id = group_members.Id_host
                INNER JOIN groups
                    ON groups.Id = group_members.Id_group
                WHERE groups.Name = :group
                ORDER BY hosts.Hostname ASC");
                $stmt->bindValue(':group', $group);
                $result = $stmt->execute();
            }
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all OS names and their count
     */
    public function getOs(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Os, Os_version, COUNT(*) as Count FROM hosts GROUP BY Os, Os_version");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all kernel names and their count
     */
    public function getKernel(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Kernel, COUNT(*) as Count FROM hosts GROUP BY Kernel");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all arch names and their count
     */
    public function getArch(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Arch, COUNT(*) as Count FROM hosts GROUP BY Arch");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all profile names and their count
     */
    public function getProfile(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Profile, COUNT(*) as Count FROM hosts GROUP BY Profile");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all environment names and their count
     */
    public function getEnvironment(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Env, COUNT(*) as Count FROM hosts GROUP BY Env");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all hosts that require a reboot
     */
    public function getRebootRequired(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Id, Hostname, Ip, Os, Os_family FROM hosts WHERE Reboot_required = 'true' ORDER BY Hostname ASC");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return all agent status and their count
     */
    public function getAgentStatus(): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM
            (SELECT COUNT(*) as Online_count
            FROM hosts
            WHERE Online_status = 'running' AND Online_status_date = :todayDate AND Online_status_time >= :maxTime),
            (SELECT COUNT(*) as Seems_stopped_count
            FROM hosts
            WHERE Online_status != 'stopped' AND (Online_status_date != :todayDate OR Online_status_time <= :maxTime)),
            (SELECT COUNT(*) as Disabled_count
            FROM hosts
            WHERE Online_status = 'disabled'),
            (SELECT COUNT(*) as Stopped_count
            FROM hosts
            WHERE Online_status = 'stopped')");
            $stmt->bindValue(':todayDate', DATE_YMD);
            $stmt->bindValue(':maxTime', date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes')));
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data['Online_count'] = $row['Online_count'];
            $data['Stopped_count'] = $row['Stopped_count'];
            $data['Seems_stopped_count'] = $row['Seems_stopped_count'];
            $data['Disabled_count'] = $row['Disabled_count'];
        }

        return $data;
    }

    /**
     *  Return all agent version and their count
     */
    public function getAgentVersion(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT Linupdate_version, COUNT(*) as Linupdate_version_count FROM hosts GROUP BY Linupdate_version");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }
}
