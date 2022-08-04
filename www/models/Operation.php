<?php

namespace Models;

use Exception;

class Operation extends Model
{
    public function __construct()
    {
        /**
         *  Ouverture d'une connexion à la base de données
         */
        $this->getConnection('main');
    }

    /**
     *  Retourne true si une opération est en cours d'exécution
     */
    public function somethingRunning()
    {
        try {
            $result = $this->db->query("SELECT Id FROM operations WHERE Status = 'running'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $operations = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $operations[] = $datas;
        }

        if (!empty($operations)) {
            return true;
        }

        return false;
    }

    /**
     *  Lister les opérations en cours d'exécution en fonction du type souhaité (opérations manuelles ou planifiées)
     */
    public function listRunning(string $type = '')
    {
        /**
         *  Cas où on souhaite tous les types
         */
        try {
            if (empty($type)) {
                $stmt = $this->db->prepare("SELECT * FROM operations WHERE Status = 'running' ORDER BY Date DESC, Time DESC");

            /**
             *  Cas où souhaite filtrer par un type en particulier
             */
            } else {
                $stmt = $this->db->prepare("SELECT * FROM operations WHERE Status = 'running' and Type = :type ORDER BY Date DESC, Time DESC");
                $stmt->bindValue(':type', $type);
            }
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $operations = array();

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $operations[] = $datas;
        }

        return $operations;
    }

    /**
     *  Lister les opérations terminées (avec ou sans erreurs)
     *  Il est possible de filtrer le type d'opération ('manual' ou 'plan')
     *  Il est possible de filtrer si le type de planification qui a lancé cette opération ('plan' ou 'regular' (planification unique ou planification récurrente))
     */
    public function listDone(string $type = '', string $planType = '')
    {
        try {
            /**
             *  Cas où on souhaite tous les types
             */
            if (empty($type) and empty($planType)) {
                $stmt = $this->db->prepare("SELECT * FROM operations WHERE Status = 'error' or Status = 'done' or Status = 'stopped' ORDER BY Date DESC, Time DESC");
            }

            /**
             *  Cas où on filtre par type d'opération seulement
             */
            if (!empty($type) and empty($planType)) {
                $stmt = $this->db->prepare("SELECT * FROM operations
                WHERE Type = :type and (Status = 'error' or Status = 'done' or Status = 'stopped')
                ORDER BY Date DESC, Time DESC");
                $stmt->bindValue(':type', $type);
            }

            /**
             *  Cas où on filtre par type de planification seulement
             */
            if (empty($type) and !empty($planType)) {
                $stmt = $this->db->prepare("SELECT * FROM operations 
                INNER JOIN planifications
                ON operations.Id_plan = planifications.Id
                WHERE planifications.Type = :plantype and (operations.Status = 'error' or operations.Status = 'done' or operations.Status = 'stopped')
                ORDER BY operations.Date DESC, operations.Time DESC");
                $stmt->bindValue(':plantype', $planType);
            }

            /**
             *  Cas où on filtre par type d'opération ET par type de planification
             */
            if (!empty($type) and !empty($planType)) {
                $stmt = $this->db->prepare("SELECT
                operations.Id,
                operations.Date,
                operations.Time,
                operations.Action,
                operations.Type,
                operations.Id_repo_source,
                operations.Id_repo_target,
                operations.Id_group,
                operations.Id_plan,
                operations.GpgCheck,
                operations.GpgResign,
                operations.Pid,
                operations.Logfile,
                operations.Status
                FROM operations 
                INNER JOIN planifications
                ON operations.Id_plan = planifications.Id
                WHERE operations.Type = :type
                and planifications.Type = :plantype
                and (operations.Status = 'error' or operations.Status = 'done' or operations.Status = 'stopped')
                ORDER BY operations.Date DESC, operations.Time DESC");
                $stmt->bindValue(':type', $type);
                $stmt->bindValue(':plantype', $planType);
            }
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Ajout d'une nouvelle opération en base de données
     */
    public function add(string $date, string $time, string $action, string $type, string $pid, string $poolId, string $logfile, string $status)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO operations (Date, Time, Action, Type, Pid, Pool_id, Logfile, Status) VALUES (:date, :time, :action, :type, :pid, :poolid, :logfile, :status)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':pid', $pid);
            $stmt->bindValue(':poolid', $poolId);
            $stmt->bindValue(':logfile', $logfile);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    public function updatePlanId(string $id, string $id_plan)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_plan=:id_plan WHERE Id=:id");
            $stmt->bindValue(':id_plan', $id_plan);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);
    }

    public function updateIdRepoSource(string $id, string $id_repo_source)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_repo_source = :id_repo_source WHERE Id = :id");
            $stmt->bindValue(':id_repo_source', $id_repo_source);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);
    }

    public function updateIdSnapSource(string $id, string $id_snap_source)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_snap_source = :id_snap_source WHERE Id = :id");
            $stmt->bindValue(':id_snap_source', $id_snap_source);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);
    }

    public function updateIdEnvSource(string $id, string $id_env_source)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_env_source = :id_env_source WHERE Id = :id");
            $stmt->bindValue(':id_env_source', $id_env_source);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);
    }

    public function updateIdRepoTarget(string $id, string $id_repo_target)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_repo_target = :id_repo_target WHERE Id = :id");
            $stmt->bindValue(':id_repo_target', $id_repo_target);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    public function updateIdSnapTarget(string $id, string $id_snap_target)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_snap_target = :id_snap_target WHERE Id = :id");
            $stmt->bindValue(':id_snap_target', $id_snap_target);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    public function updateIdEnvTarget(string $id, string $id_env_target)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_env_target = :id_env_target WHERE Id = :id");
            $stmt->bindValue(':id_env_target', $id_env_target);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    public function updateIdGroup(string $id, string $id_group)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_group=:id_group WHERE Id=:id");
            $stmt->bindValue(':id_group', $id_group);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);
    }

    public function updateGpgCheck(string $id, string $gpgCheck)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET GpgCheck=:gpgCheck WHERE Id=:id");
            $stmt->bindValue(':gpgCheck', $gpgCheck);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);
    }

    public function updateGpgResign(string $id, string $gpgResign)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET GpgResign=:gpgResign WHERE Id=:id");
            $stmt->bindValue(':gpgResign', $gpgResign);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
        unset($stmt);
    }

    /**
     *  CLOTURE D'UNE OPERATION
     *  Modifie le status en BDD
     */
    public function closeOperation(string $id, string $status, string $duration)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Status = :status, Duration = :duration WHERE Id = :id");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':duration', $duration);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne les opérations exécutées ou en cours d'exécution par une planification à partir de son Id
     */
    public function getOperationsByPlanId(string $planId, string $status)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM operations WHERE Id_plan = :planId and Status = :status");
            $stmt->bindValue(':planId', $planId);
            $stmt->bindValue(':status', $status);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            Controllers\Common::dbError($e);
        }

        $data = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Retourne l'Id d'une planification en fonction du PID d'opération spécifié
     */
    public function getPlanIdByPid(string $pid)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id_plan FROM operations WHERE Pid = :pid and Status = 'running'");
            $stmt->bindValue(':pid', $pid);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        $planId = '';

        while ($datas = $result->fetchArray(SQLITE3_ASSOC)) {
            $planId = $datas['Id_plan'];
        }

        return $planId;
    }

    /**
     *  Met à jour le status d'une opération à partir du PID spécifié
     */
    public function stopRunningOp(string $pid)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Status = 'stopped' WHERE Pid = :pid and Status = 'running'");
            $stmt->bindValue(':pid', $pid);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne toutes les informations en base de données concernant une opération
     */
    public function getAll(string $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM operations WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $op = $row;
        }

        return $op;
    }
}
