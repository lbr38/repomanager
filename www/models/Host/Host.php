<?php

namespace Models\Host;

use Exception;
use Controllers\Database\Log as DbLog;

class Host extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('hosts');
    }

    /**
     *  Return host information from its Id
     */
    public function get(int $id): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * from hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return host's IP by its Id
     */
    public function getIp(int $id): string
    {
        $ip = '';

        try {
            $stmt = $this->db->prepare("SELECT Ip FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $ip = $row['Ip'];
        }

        return $ip;
    }

    /**
     *  Return the host's Id from its authId
     */
    public function getIdByAuth(string $authId): int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :authId");
            $stmt->bindValue(':authId', $authId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return the host's Id from its hostname
     */
    public function getIdByHostname(string $hostname): int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', $hostname);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return hosts settings
     */
    public function getSettings(): array
    {
        $settings = [];

        try {
            $result = $this->db->query("SELECT * FROM settings");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $settings = $row;
        }

        return $settings;
    }

    /**
     *  Edit the display settings on the hosts page
     */
    public function setSettings(string $packagesConsideredOutdated, string $packagesConsideredCritical): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE settings SET pkgs_count_considered_outdated = :packagesConsideredOutdated, pkgs_count_considered_critical = :packagesConsideredCritical");
            $stmt->bindValue(':packagesConsideredOutdated', $packagesConsideredOutdated);
            $stmt->bindValue(':packagesConsideredCritical', $packagesConsideredCritical);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return true if the host Id exists in the database
     */
    public function existsId(int $id): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if the hostname exists in the database
     */
    public function existsHostname(string $hostname): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Hostname FROM hosts WHERE Hostname = :hostname");
            $stmt->bindValue(':hostname', $hostname);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if the Id/token pair is valid
     */
    public function checkIdToken(string $authId, string $token): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM hosts WHERE AuthId = :authId and Token = :token");
            $stmt->bindValue(':authId', $authId);
            $stmt->bindValue(':token', $token);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Add a new host in database
     */
    public function add(string $ip, string $hostname, string $authId, string $token, string $onlineStatus, string $date, string $time): void
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
            DbLog::error($e);
        }
    }

    /**
     *  Reset host data
     */
    public function reset(int $hostId): void
    {
        try {
            // Reset host general informations
            $stmt = $this->db->prepare("UPDATE hosts SET
            Os = null,
            Os_version = null,
            Os_family = null,
            Cpu = null,
            Ram = null,
            Network = null,
            Kernel = null,
            Arch = null,
            Type = null,
            Profile = null,
            Env = null

            WHERE id = :id");

            $stmt->bindValue(':id', $hostId);
            $stmt->execute();

            // Retrieve all requests made to the host
            $stmt = $this->db->prepare("SELECT Id FROM requests WHERE Id_host = :id");
            $stmt->bindValue(':id', $hostId);
            $result = $stmt->execute();

            // Delete all requests logs files
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                if (file_exists(WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log')) {
                    if (!unlink(WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log')) {
                        throw new Exception('Unable to delete request log file: ' . WS_REQUESTS_LOGS_DIR . '/request-' . $row['Id'] . '.log');
                    }
                }
            }

            // Delete all requests in requests table
            $stmt = $this->db->prepare("DELETE FROM requests WHERE Id_host = :id");
            $stmt->bindValue(':id', $hostId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete a host from database
     */
    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM hosts WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Add the host to the specified group in the database
     */
    public function addToGroup(string $hostId, string $groupId): void
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
            DbLog::error($e);
        }

        // If the host is already present, do nothing
        if (!$this->db->isempty($result)) {
            return;
        }

        try {
            $stmt = $this->db->prepare("INSERT INTO group_members (Id_host, Id_group) VALUES (:id_host, :id_group)");
            $stmt->bindValue(':id_host', $hostId);
            $stmt->bindValue(':id_group', $groupId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Remove the host from the specified group
     */
    public function removeFromGroup(string $hostId, ?int $groupId = null): void
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
            DbLog::error($e);
        }
    }
}
