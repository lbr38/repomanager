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
        string $notificationOnError,
        string $notificationOnSuccess,
        string $mailRecipient = null,
        string $reminder = null
    ) {
        try {
            $stmt = $this->db->prepare("INSERT INTO Planifications ('Type', 'Frequency', 'Day', 'Date', 'Time', 'Action', 'Id_snap', 'Id_group', 'Target_env', 'Gpgcheck', 'Gpgresign', 'Reminder', 'Notification_error', 'Notification_success', 'Mail_recipient', 'Status') VALUES (:plantype, :frequency, :day, :date, :time, :action, :snapId, :groupId, :targetEnv, :gpgcheck, :gpgresign, :reminder, :notification_error, :notification_success, :mailrecipient, 'queued')");
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
     *  Suppression d'une planification en base de données
     */
    public function remove(string $planId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE planifications SET Status = 'canceled' WHERE Id = :id");
            $stmt->bindValue(':id', $planId);
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
     *  Retourne les informations complètes d'un planification en base de données
     */
    public function getInfo(string $planId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM planifications WHERE Id = :id");
            $stmt->bindValue(':id', $planId);
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
     *  Retourne la liste des planifications en attente d'exécution
     */
    public function getQueue()
    {
        $query = $this->db->query("SELECT * FROM planifications WHERE Status = 'queued'");

        $queue = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) {
            $queue[] = $datas;
        }

        return $queue;
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
     *  Liste les planifications en cours d'exécution
     */
    public function listRunning()
    {
        $query = $this->db->query("SELECT * FROM planifications WHERE Status = 'running' ORDER BY Date DESC, Time DESC");

        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) {
            $plans[] = $datas;
        }

        return $plans;
    }

    /**
    *  Liste les planifications terminées (tout status compris sauf canceled)
    */
    public function listDone()
    {
        $query = $this->db->query("SELECT * FROM planifications WHERE Status = 'done' or Status = 'error' or Status = 'stopped' ORDER BY Date DESC, Time DESC");

        $plans = array();

        while ($datas = $query->fetchArray(SQLITE3_ASSOC)) {
            $plans[] = $datas;
        }

        return $plans;
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
}
