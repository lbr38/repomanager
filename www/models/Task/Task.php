<?php

namespace Models\Task;

use Exception;

class Task extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open database
         */
        $this->getConnection('main');
    }

    /**
     *  Get task details by Id
     *  @param int $id
     *  @return array
     */
    public function getById(int $id) : array
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT * FROM tasks WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return the Id of a task from its PID
     *  @param int $pid
     *  @return int
     */
    public function getIdByPid(int $pid) : int
    {
        $id = 0;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM tasks WHERE Pid = :pid");
            $stmt->bindValue(':pid', $pid);
            $result = $stmt->execute();
        } catch (Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Update task status from specified PID
     */
    public function setStatus(int $id, string $status) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Status = :status WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update plan Id in database
     */
    // public function updatePlanId(string $id, string $planId)
    // {
    //     try {
    //         $stmt = $this->db->prepare("UPDATE operations SET Id_plan=:id_plan WHERE Id=:id");
    //         $stmt->bindValue(':id_plan', $planId);
    //         $stmt->bindValue(':id', $id);
    //         $stmt->execute();
    //     } catch (\Exception $e) {
    //         \Controllers\Common::dbError($e);
    //     }
    // }

    /**
     *  Update repo name in database
     */
    public function updateTargetRepo(string $id, string $name) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Target_repo_id = :repoId WHERE Id = :id");
            $stmt->bindValue(':repoId', $name);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update source snap Id in database
     */
    public function updateSourceSnap(string $id, string $snapId) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Source_snap_id = :snapId WHERE Id = :id");
            $stmt->bindValue(':snapId', $snapId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update target snap Id in database
     */
    public function updateTargetSnap(string $id, string $snapId) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Target_snap_id = :snapId WHERE Id = :id");
            $stmt->bindValue(':snapId', $snapId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update target env Id in database
     */
    public function updateTargetEnv(string $id, string $envId) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Target_env_id = :envId WHERE Id = :id");
            $stmt->bindValue(':envId', $envId);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update group Id in database
     */
    // public function updateGroup(string $id, string $groupId)
    // {
    //     try {
    //         $stmt = $this->db->prepare("UPDATE tasks SET Group_id = :groupId WHERE Id = :id");
    //         $stmt->bindValue(':groupId', $groupId);
    //         $stmt->bindValue(':id', $id);
    //         $stmt->execute();
    //     } catch (\Exception $e) {
    //         \Controllers\Common::dbError($e);
    //     }
    // }

    /**
     *  Update GPG check in database
     */
    public function updateGpgCheck(string $id, string $gpgCheck) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Gpg_check = :gpgCheck WHERE Id = :id");
            $stmt->bindValue(':gpgCheck', $gpgCheck);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update GPG sign in database
     */
    public function updateGpgSign(string $id, string $gpgSign) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Gpg_sign = :gpgSign WHERE Id = :id");
            $stmt->bindValue(':gpgSign', $gpgSign);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Retourne true si une opération est en cours d'exécution
     */
    public function somethingRunning()
    {
        $data = array();

        try {
            $result = $this->db->query("SELECT Id FROM tasks WHERE Status = 'running'");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        if (!empty($data)) {
            return true;
        }

        return false;
    }

    /**
     *  List all running tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
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
                $query = "SELECT * FROM tasks
                WHERE Status = 'running'
                ORDER BY Date DESC, Time DESC";

            /**
             *  Case where we want to filter by task type only
             */
            } else {
                $query = "SELECT * FROM tasks
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
     *  List all done tasks (with or without errors)
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to filter the type of planification that launched this task ('scheduled' or 'regular' (unique planification or recurrent planification))
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
                $query = "SELECT * FROM tasks
                WHERE Status = 'error' or Status = 'done' or Status = 'stopped'
                ORDER BY Date DESC, Time DESC";
            }

            /**
             *  Case where we want to filter by task type only
             */
            if (!empty($type) and empty($planType)) {
                $query = "SELECT * FROM tasks
                WHERE Type = :type and (Status = 'error' or Status = 'done' or Status = 'stopped')
                ORDER BY Date DESC, Time DESC";
            }

            /**
             *  Case where we want to filter by planification type only
             */
            if (empty($type) and !empty($planType)) {
                $query = "SELECT * FROM tasks 
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
     *  Add a new task to the database
     */
    public function add(string $date, string $time, string $action, string $type, string $pid, string $poolId, string $logfile, string $status)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO tasks (Date, Time, Action, Type, Pid, Task_pool_id, Logfile, Status) VALUES (:date, :time, :action, :type, :pid, :poolid, :logfile, :status)");
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
     *  Close a task
     */
    public function close(string $id, string $status, string $duration)
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Status = :status, Duration = :duration WHERE Id = :id");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':duration', $duration);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }
}
