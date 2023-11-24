<?php

namespace Models\Operation;

use Exception;

class Operation extends \Models\Model
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
     *  List all running operations
     *  It is possible to filter the type of operation ('manual' or 'plan')
     *  It is possible to add an offset to the request
     */
    public function listRunning(string $type, bool $withOffset, int $offset)
    {
        $data = array();

        /**
         *  Case where we want all types
         */
        try {
            if (empty($type)) {
                $query = "SELECT * FROM operations
                WHERE Status = 'running'
                ORDER BY Date DESC, Time DESC";

            /**
             *  Case where we want to filter by operation type only
             */
            } else {
                $query = "SELECT * FROM operations
                WHERE Status = 'running' and Type = :type
                ORDER BY Date DESC, Time DESC";
            }

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
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  List all done operations (with or without errors)
     *  It is possible to filter the type of operation ('manual' or 'plan')
     *  It is possible to filter the type of planification that launched this operation ('plan' or 'regular' (unique planification or recurrent planification))
     *  It is possible to add an offset to the request
     */
    public function listDone(string $type, string $planType, bool $withOffset, int $offset)
    {
        $data = array();

        try {
            /**
             *  Case where we want all types
             */
            if (empty($type) and empty($planType)) {
                $query = "SELECT * FROM operations
                WHERE Status = 'error' or Status = 'done' or Status = 'stopped'
                ORDER BY Date DESC, Time DESC";
            }

            /**
             *  Case where we want to filter by operation type only
             */
            if (!empty($type) and empty($planType)) {
                $query = "SELECT * FROM operations
                WHERE Type = :type and (Status = 'error' or Status = 'done' or Status = 'stopped')
                ORDER BY Date DESC, Time DESC";
            }

            /**
             *  Case where we want to filter by planification type only
             */
            if (empty($type) and !empty($planType)) {
                $query = "SELECT * FROM operations 
                INNER JOIN planifications
                ON operations.Id_plan = planifications.Id
                WHERE planifications.Type = :plantype and (operations.Status = 'error' or operations.Status = 'done' or operations.Status = 'stopped')
                ORDER BY operations.Date DESC, operations.Time DESC";
            }

            /**
             *  Case where we want to filter by operation type AND planification type
             */
            if (!empty($type) and !empty($planType)) {
                $query = "SELECT
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
                ORDER BY operations.Date DESC, operations.Time DESC";
            }

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

            /**
             *  Bind values if exists
             */
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':plantype', $planType);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':plantype', $planType);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
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

    /**
     *  Update plan Id in database
     */
    public function updatePlanId(string $id, string $planId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_plan=:id_plan WHERE Id=:id");
            $stmt->bindValue(':id_plan', $planId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update repo name in database
     */
    public function updateTargetRepo(string $id, string $name)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_repo_target = :id_repo_target WHERE Id = :id");
            $stmt->bindValue(':id_repo_target', $name);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update source repo in database
     */
    public function updateSourceRepo(string $id, string $sourceRepo)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_repo_source = :id_repo_source WHERE Id = :id");
            $stmt->bindValue(':id_repo_source', $sourceRepo);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update source snap Id in database
     */
    public function updateSourceSnap(string $id, string $snapId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_snap_source = :id_snap_source WHERE Id = :id");
            $stmt->bindValue(':id_snap_source', $snapId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update target snap Id in database
     */
    public function updateTargetSnap(string $id, string $snapId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_snap_target = :id_snap_target WHERE Id = :id");
            $stmt->bindValue(':id_snap_target', $snapId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update source env in database
     */
    public function updateSourceEnv(string $id, string $envId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_env_source = :id_env_source WHERE Id = :id");
            $stmt->bindValue(':id_env_source', $envId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update target env Id in database
     */
    public function updateTargetEnv(string $id, string $envId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_env_target = :id_env_target WHERE Id = :id");
            $stmt->bindValue(':id_env_target', $envId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update group Id in database
     */
    public function updateGroup(string $id, string $groupId)
    {
        try {
            $stmt = $this->db->prepare("UPDATE operations SET Id_group=:id_group WHERE Id=:id");
            $stmt->bindValue(':id_group', $groupId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        unset($stmt);
    }

    /**
     *  Update GPG check in database
     */
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

    /**
     *  Update GPG resign in database
     */
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
