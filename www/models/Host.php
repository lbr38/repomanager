<?php

namespace Models;

use Exception;

class Host extends Model
{
    public function __construct()
    {
        $this->getConnection('hosts');
    }

    /**
     *  Return all hosts from a group
     */
    public function listByGroup(string $groupName) : array
    {
        $hostsIn = array();

        /**
         *  If the group name is 'Default' (fictitious group) then we display all hosts without a group
         */
        try {
            if ($groupName == 'Default') {
                $hostsInGroup = $this->db->query("SELECT *
                FROM hosts
                WHERE Id NOT IN (SELECT Id_host FROM group_members)
                ORDER BY hosts.Hostname ASC");
            } else {
                /**
                 *  Note: do not use SELECT *
                 *  As it is a join, you must specify the desired data
                 */
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
                WHERE groups.Name=:groupname
                ORDER BY hosts.Hostname ASC");
                $stmt->bindValue(':groupname', $groupName);
                $hostsInGroup = $stmt->execute();
                unset($stmt);
            }
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($datas = $hostsInGroup->fetchArray(SQLITE3_ASSOC)) {
            $hostsIn[] = $datas;
        }

        return $hostsIn;
    }

    /**
     *  Return the host Id from its authId
     */
    public function getIdByAuth(string $authId) : int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :authId");
            $stmt->bindValue(':authId', $authId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return the host Id from its hostname
     */
    public function getIdByHostname(string $hostname) : int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', $hostname);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return all host information from its Id
     */
    public function getAll(string $id) : array
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT * from hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return hosts that have the specified kernel
     */
    public function getHostWithKernel(string $kernel) : array
    {
        $hosts = array();

        try {
            $stmt = $this->db->prepare("SELECT Id, Hostname, Ip, Os, Os_family FROM hosts WHERE Kernel = :kernel ORDER BY Hostname ASC");
            $stmt->bindValue(':kernel', $kernel);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Return hosts that have the specified profile
     */
    public function getHostWithProfile(string $profile) : array
    {
        $hosts = array();

        try {
            $stmt = $this->db->prepare("SELECT Id, Hostname, Ip, Os, Os_family FROM hosts WHERE Profile = :profile ORDER BY Hostname ASC");
            $stmt->bindValue(':profile', $profile);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Return true if the Id/token pair is valid
     */
    public function checkIdToken(string $authId, string $token) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Return all hosts
     */
    public function listAll() : array
    {
        $datas = array();

        $result = $this->db->query("SELECT * FROM hosts");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Return all OS names and their count
     */
    public function listCountOS() : array
    {
        $os = array();

        $result = $this->db->query("SELECT Os, Os_version, COUNT(*) as Os_count FROM hosts GROUP BY Os, Os_version");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $os[] = $row;
        }

        return $os;
    }

    /**
     *  Return all kernel names and their count
     */
    public function listCountKernel() : array
    {
        $kernel = array();

        $result = $this->db->query("SELECT Kernel, COUNT(*) as Kernel_count FROM hosts GROUP BY Kernel");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $kernel[] = $row;
        }

        return $kernel;
    }

    /**
     *  Return all arch names and their count
     */
    public function listCountArch() : array
    {
        $arch = array();

        $result = $this->db->query("SELECT Arch, COUNT(*) as Arch_count FROM hosts GROUP BY Arch");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $arch[] = $row;
        }

        return $arch;
    }

    /**
     *  Return all env names and their count
     */
    public function listCountEnv() : array
    {
        $env = array();

        $result = $this->db->query("SELECT Env, COUNT(*) as Env_count FROM hosts GROUP BY Env");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $env[] = $row;
        }

        return $env;
    }

    /**
     *  Return all profile names and their count
     */
    public function listCountProfile() : array
    {
        $profile = array();

        $result = $this->db->query("SELECT Profile, COUNT(*) as Profile_count FROM hosts GROUP BY Profile");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $profile[] = $row;
        }

        return $profile;
    }

    /**
     *  Return all agent status and their count
     */
    public function listCountAgentStatus() : array
    {
        $agentStatus = array();

        $stmt = $this->db->prepare("SELECT * FROM
        (SELECT COUNT(*) as Linupdate_agent_status_online_count
        FROM hosts
        WHERE Online_status = 'running' AND Online_status_date = :todayDate AND Online_status_time >= :maxTime),
        (SELECT COUNT(*) as Linupdate_agent_status_seems_stopped_count
        FROM hosts
        WHERE Online_status != 'stopped' AND (Online_status_date != :todayDate OR Online_status_time <= :maxTime)),
        (SELECT COUNT(*) as Linupdate_agent_status_disabled_count
        FROM hosts
        WHERE Online_status = 'disabled'),
        (SELECT COUNT(*) as Linupdate_agent_status_stopped_count
        FROM hosts
        WHERE Online_status = 'stopped')");
        $stmt->bindValue(':todayDate', DATE_YMD);
        $stmt->bindValue(':maxTime', date('H:i:s', strtotime(date('H:i:s') . ' - 70 minutes')));
        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $agentStatus['Linupdate_agent_status_online_count'] = $row['Linupdate_agent_status_online_count'];
            $agentStatus['Linupdate_agent_status_stopped_count'] = $row['Linupdate_agent_status_stopped_count'];
            $agentStatus['Linupdate_agent_status_seems_stopped_count'] = $row['Linupdate_agent_status_seems_stopped_count'];
            $agentStatus['Linupdate_agent_status_disabled_count'] = $row['Linupdate_agent_status_disabled_count'];
        }

        return $agentStatus;
    }

    /**
     *  Return all agent version and their count
     */
    public function listCountAgentVersion() : array
    {
        $agent = array();

        $result = $this->db->query("SELECT Linupdate_version, COUNT(*) as Linupdate_version_count FROM hosts GROUP BY Linupdate_version");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $agent[] = $row;
        }

        return $agent;
    }

    /**
     *  Return all hosts that require a reboot
     */
    public function listRebootRequired() : array
    {
        $hosts = array();

        $result = $this->db->query("SELECT Id, Hostname, Ip, Os, Os_family FROM hosts WHERE Reboot_required = 'true' ORDER BY Hostname ASC");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hosts[] = $row;
        }

        return $hosts;
    }

    /**
     *  Return true if the IP exists in the database
     */
    public function ipExists(string $ip) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Ip FROM hosts WHERE Ip = :ip");
            $stmt->bindValue(':ip', $ip);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if the hostname exists in the database
     */
    public function hostnameExists(string $hostname) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', $hostname);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return hosts settings
     */
    public function getSettings() : array
    {
        $settings = array();

        try {
            $result = $this->db->query("SELECT * FROM settings");
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings = $row;
        }

        return $settings;
    }

    /**
     *  Edit the display settings on the hosts page
     */
    public function setSettings(string $packagesConsideredOutdated, string $packagesConsideredCritical) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE settings SET pkgs_count_considered_outdated = :packagesConsideredOutdated, pkgs_count_considered_critical = :packagesConsideredCritical");
            $stmt->bindValue(':packagesConsideredOutdated', $packagesConsideredOutdated);
            $stmt->bindValue(':packagesConsideredCritical', $packagesConsideredCritical);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Return the hostname of the host by its Id
     */
    public function getHostnameById(int $id) : string
    {
        $hostname = '';

        try {
            $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $hostname = $row['Hostname'];
        }

        return $hostname;
    }

    /**
     *  Return the IP of a host from its Id
     */
    public function getIpById(string $id) : string
    {
        $ip = '';

        try {
            $stmt = $this->db->prepare("SELECT Ip FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ip = $row['Ip'];
        }

        return $ip;
    }

    /**
     *  Add a new host in database
     */
    public function add(string $ip, string $hostname, string $authId, string $token, string $onlineStatus, string $date, string $time) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO hosts (Ip, Hostname, AuthId, Token, Online_status, Online_status_date, Online_status_time) VALUES (:ip, :hostname, :id, :token, :online_status, :date, :time)");
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':hostname', $hostname);
            $stmt->bindValue(':id', $authId);
            $stmt->bindValue(':token', $token);
            $stmt->bindValue(':online_status', $onlineStatus);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Delete a host from database
     */
    public function delete(int $id) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Reset host data
     */
    public function reset(int $hostId) : void
    {
        try {
            /**
             *  Reset host general informations
             */
            $stmt = $this->db->prepare("UPDATE hosts SET Os = null, Os_version = null, Profile = null, Env = null, Kernel = null, Arch = null WHERE id = :id");
            $stmt->bindValue(':id', $hostId);
            $stmt->execute();

            /**
             *  Retrieve all requests made to the host
             */
            $stmt = $this->db->prepare("SELECT Id FROM requests WHERE Id_host = :id");
            $stmt->bindValue(':id', $hostId);
            $result = $stmt->execute();

            /**
             *  Delete all requests logs files
             */
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if (file_exists(WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log')) {
                    if (!unlink(WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log')) {
                        throw new Exception('Unable to delete request log file: ' . WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log');
                    }
                }
            }

            /**
             *  Delete all requests in requests table
             */
            $stmt = $this->db->prepare("DELETE FROM requests WHERE Id_host = :id");
            $stmt->bindValue(':id', $hostId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Return true if the host Id exists in the database
     */
    public function existsId(string $id) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Add the host to the specified group in the database
     */
    public function addToGroup(string $hostId, string $groupId) : void
    {
        /**
         *  First, check if the host is not already a member of the group
         *  (refreshing the <select> can cause the repo to be added to the group twice, so we do this check to avoid this bug)
         */
        try {
            $stmt = $this->db->prepare("SELECT Id FROM group_members WHERE Id_host = :hostId AND Id_group = :groupId");
            $stmt->bindValue(':hostId', $hostId);
            $stmt->bindValue(':groupId', $groupId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        /**
         *  If the host is already present, do nothing
         */
        if ($this->db->isempty($result) === false) {
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO group_members (Id_host, Id_group) VALUES (:id_host, :id_group)");
            $stmt->bindValue(':id_host', $hostId);
            $stmt->bindValue(':id_group', $groupId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Remove the host from the specified group
     */
    public function removeFromGroup(string $hostId, string $groupId = null) : void
    {
        try {
            // If the groupId is specified
            if (!empty($groupId)) {
                $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_host = :hostId AND Id_group = :groupId");
                $stmt->bindValue(':groupId', $groupId);
            } else {
                $stmt = $this->db->prepare("DELETE FROM group_members WHERE Id_host = :hostId");
            }
            $stmt->bindValue(':hostId', $hostId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Return the number of hosts using the specified profile
     */
    public function countByProfile(string $profile) : int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(Id) as count FROM hosts WHERE Profile = :profile");
            $stmt->bindValue(':profile', $profile);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            return $row['count'];
        }
    }
}
