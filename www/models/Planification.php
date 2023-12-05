<?php

namespace Models;

use Exception;

class Planification extends Model
{
    public function __construct()
    {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
    }

    /**
     *  Return planification info, by Id
     */
    public function get(string $id)
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT * FROM planifications WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return list of planifications with specified status
     *  It is possible to add an offset to the request
     */
    public function getByStatus(array $status, bool $withOffset, int $offset)
    {
        $data = array();
        $i = 0;

        $query = "SELECT * FROM planifications";

        foreach ($status as $stat) {
            if ($i === 0) {
                $query .= " WHERE Status = '" . $stat . "'";
            } else {
                $query .= " OR Status = '" . $stat . "'";
            }

            $i++;
        }

        $query = $query . " ORDER BY Date DESC, Time DESC";

        /**
         *  Add offset if needed
         */
        if ($withOffset === true) {
            $query .= " LIMIT 10 OFFSET :offset";
        }

        /**
         *  Prepare query
         */
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

        $result = $stmt->execute();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Ajout d'une planification en base de données
     */
    public function add(
        string $type,
        string $frequency = null,
        string $day = null,
        string $date = null,
        string $time = null,
        string $action,
        string $snapId = null,
        string $groupId = null,
        string $targetEnv = null,
        string $gpgCheck = null,
        string $gpgResign = null,
        string $onlySyncDifference = null,
        string $notificationOnError,
        string $notificationOnSuccess,
        string $mailRecipient = null,
        string $reminder = null
    ) {
        try {
            $stmt = $this->db->prepare("INSERT INTO Planifications ('Type', 'Frequency', 'Day', 'Date', 'Time', 'Action', 'Id_snap', 'Id_group', 'Target_env', 'Gpgcheck', 'Gpgresign', 'OnlySyncDifference', 'Reminder', 'Notification_error', 'Notification_success', 'Mail_recipient', 'Status') VALUES (:plantype, :frequency, :day, :date, :time, :action, :snapId, :groupId, :targetEnv, :gpgcheck, :gpgresign, :onlySyncDifference, :reminder, :notification_error, :notification_success, :mailrecipient, 'queued')");
            $stmt->bindValue(':plantype', $type);
            $stmt->bindValue(':frequency', $frequency);
            $stmt->bindValue(':day', $day);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->bindValue(':groupId', $groupId);
            $stmt->bindValue(':targetEnv', $targetEnv);
            $stmt->bindValue(':gpgcheck', $gpgCheck);
            $stmt->bindValue(':gpgresign', $gpgResign);
            $stmt->bindValue(':onlySyncDifference', $onlySyncDifference);
            $stmt->bindValue(':notification_error', $notificationOnError);
            $stmt->bindValue(':notification_success', $notificationOnSuccess);
            $stmt->bindValue(':mailrecipient', $mailRecipient);
            $stmt->bindValue(':reminder', $reminder);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne true si l'Id de planification spécifié existe en base de données
     */
    public function existsId(string $planId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM planifications WHERE Id = :id");
            $stmt->bindValue(':id', $planId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Retourne le status d'une planification en base de données
     */
    public function getStatus(string $planId)
    {
        try {
            $stmt = $this->db->prepare("SELECT Status FROM planifications WHERE Id = :id");
            $stmt->bindValue(':id', $planId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $status = $row['Status'];
        }

        return $status;
    }

    /**
     *  Défini le status d'une planification en base de données
     */
    public function setStatus(string $planId, string $status)
    {
        try {
            $stmt = $this->db->prepare("UPDATE planifications SET Status = :status WHERE Id = :id");
            $stmt->bindValue(':id', $planId);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Met à jour l'Id de snapshot à traiter dans une planification
     */
    public function setSnapId(string $planId, string $snapId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE planifications SET Id_snap = :snapId WHERE Id = :id");
            $stmt->bindValue(':id', $planId);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Liste la dernière planification exécutée
     */
    public function listLast()
    {
        $query = $this->db->query("SELECT Date, Time, Status FROM planifications WHERE Type = 'plan' and (Status = 'done' or Status = 'error') ORDER BY Date DESC, Time DESC LIMIT 1");

        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) {
            $plans = $datas;
        }

        return $plans;
    }

    /**
     *  Liste la prochaine planification qui sera exécutée
     */
    public function listNext()
    {
        $query  = $this->db->query("SELECT Date, Time FROM planifications WHERE Type = 'plan' and Status = 'queued' ORDER BY Date ASC, Time ASC LIMIT 1");

        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) {
            $plans = $datas;
        }

        return $plans;
    }

    /**
     *  Mise à jour du status d'une planification en base de données, suite à son exécution
     */
    public function closeUpdateStatus(string $planId, string $status, string $errorMsg = null, string $logName)
    {
        try {
            $stmt = $this->db->prepare("UPDATE planifications SET Status = :status, Error = :errorMsg, Logfile = :logfile WHERE Id = :planId");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':errorMsg', $errorMsg);
            $stmt->bindValue(':logfile', $logName);
            $stmt->bindValue(':planId', $planId);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Return log file name of all operations launched by this planification
     */
    public function getOperationLogName(int $planId)
    {
        $log = array();

        try {
            $stmt = $this->db->prepare("SELECT Logfile FROM operations WHERE Id_plan = :planId");
            $stmt->bindValue(':planId', $planId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $log[] = $row['Logfile'];
        }

        return $log;
    }
}
