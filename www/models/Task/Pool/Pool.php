<?php

namespace Models\Task\Pool;

use Exception;

class Pool extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open a connection to the database
         */
        $this->getConnection('main');
    }

    /**
     *  Get pool details by Id
     */
    public function getById(int $id)
    {
        $data = array();

        try {
            $stmt = $this->db->prepare("SELECT * FROM tasks_pool WHERE Id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
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
     *  Create a new task in task pool and return the pool Id
     *  @param array $params
    *   @return int
     */
    public function new(array $params) : int
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("INSERT INTO tasks_pool (Parameters) VALUES (:params)");
            $stmt->bindValue(':params', json_encode($params), SQLITE3_TEXT);
            $stmt->execute();
            $id = $this->db->lastInsertRowID();
        } catch (Exception $e) {
            \Controllers\Common::dbError($e);
        }

        return $id;
    }
}
