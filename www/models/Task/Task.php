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
            $stmt = $this->db->prepare("SELECT * FROM operations WHERE Id = :id");
        } catch (Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
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
     *  Met à jour le status d'une opération à partir du PID spécifié
     */
    public function setStatus(int $id, string $status)
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
}
