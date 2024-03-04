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
     *  Get task PID by Id
     */
    public function getPidById(int $id) : int
    {
        try {
            $stmt = $this->db->prepare("SELECT Pid FROM tasks WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $pid = $row['Pid'];
        }

        return $pid;
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
     *  Update PID in database
     */
    public function updatePid(int $id, int $pid) :void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Pid = :pid WHERE Id = :id");
            $stmt->bindValue(':pid', $pid);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update logfile in database
     */
    public function updateLogfile(int $id, string $logfile) :void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Logfile = :logfile WHERE Id = :id");
            $stmt->bindValue(':logfile', $logfile);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update status in database
     */
    public function updateStatus(int $id, string $status) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Status = :status WHERE Id = :id");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update duration in database
     */
    public function updateDuration(int $id, string $duration) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Duration = :duration WHERE Id = :id");
            $stmt->bindValue(':duration', $duration);
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
     *  List all scheduled tasks
     *  It is possible to add an offset to the request
     */
    public function listScheduled(bool $withOffset, int $offset)
    {
        $data = array();

        try {
            $query = "SELECT * FROM tasks
            WHERE TYPE = 'scheduled'
            AND (Status = 'scheduled'
            OR Status = 'disabled')
            ORDER BY Schedule_date DESC, Schedule_time DESC";

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
     *  It is possible to add an offset to the request
     */
    public function listDone(string $type, bool $withOffset, int $offset)
    {
        $data = array();

        try {
            /**
             *  Case where we want all types
             */
            if (empty($type)) {
                $query = "SELECT * FROM tasks
                WHERE Status = 'new'
                OR Status = 'error'
                OR Status = 'done'
                OR Status = 'stopped'
                ORDER BY Date DESC, Time DESC";
            }

            /**
             *  Case where we want to filter by type
             */
            if (!empty($type)) {
                $query = "SELECT * FROM tasks
                WHERE Type = :type
                AND (Status = 'new'
                OR Status = 'error'
                OR Status = 'done'
                OR Status = 'stopped')
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
     *  Add a new task to the database
     */
    public function new(string $type, string $rawParams, string $status) : int
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES (:type, :rawParams, :status)");
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':rawParams', $rawParams);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        /**
         *  Return the Id of the new task
         */
        return $this->db->lastInsertRowID();
    }

    /**
     *  Set date in database
     */
    public function setDate(int $id, string $date) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Date = :date WHERE Id = :id");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set time in database
     */
    public function setTime(int $id, string $time) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Time = :time WHERE Id = :id");
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule date in database
     */
    public function setScheduleDate(int $id, string $date) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_date = :date WHERE Id = :id");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule time in database
     */
    public function setScheduleTime(int $id, string $time) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_time = :time WHERE Id = :id");
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule frequency in database
     */
    public function setScheduleFrequency(int $id, string $frequency) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_frequency = :frequency WHERE Id = :id");
            $stmt->bindValue(':frequency', $frequency);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule day in database
     */
    public function setScheduleDay(int $id, string $day) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_day = :day WHERE Id = :id");
            $stmt->bindValue(':day', $day);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule reminder in database
     */
    public function setScheduleReminder(int $id, string $reminder) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_reminder = :reminder WHERE Id = :id");
            $stmt->bindValue(':reminder', $reminder);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule notify error in database
     */
    public function setScheduleNotifyError(int $id, string $notifyError) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_notify_error = :notifyError WHERE Id = :id");
            $stmt->bindValue(':notifyError', $notifyError);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule notify success in database
     */
    public function setScheduleNotifySuccess(int $id, string $notifySuccess) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_notify_success = :notifySuccess WHERE Id = :id");
            $stmt->bindValue(':notifySuccess', $notifySuccess);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set schedule recipient in database
     */
    public function setScheduleRecipient(int $id, string $recipient) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Schedule_recipient = :recipient WHERE Id = :id");
            $stmt->bindValue(':recipient', $recipient);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Close a task
     */
    public function close(int $id, string $status, string $duration)
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

    /**
     *  Enable a task
     */
    public function enable(int $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Status = 'scheduled' WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Disable a task
     */
    public function disable(int $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE tasks SET Status = 'disabled' WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Delete a task
     */
    public function delete(int $id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM tasks WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }
}
