<?php

namespace Models\Log;

use Exception;

class Log extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get all logs or logs of a specific type
     */
    public function getUnread(string $type = null, int $limit = 0)
    {
        $logs = array();

        try {
            if ($type == null) {
                $query = "SELECT * FROM logs WHERE Status = 'new' ORDER BY Date DESC, Time DESC";
            }
            if ($type == 'error') {
                $query = "SELECT * FROM logs WHERE Status = 'new' AND Type = 'error' ORDER BY Date DESC, Time DESC";
            }
            if ($type == 'info') {
                $query = "SELECT * FROM logs WHERE Status = 'new' AND Type = 'info' ORDER BY Date DESC, Time DESC";
            }

            /**
             *  Set a limit if specified
             */
            if ($limit > 0) {
                $query .= " LIMIT :limit";
            }

            $stmt = $this->db->prepare($query);
            if ($limit > 0) {
                $stmt->bindValue(':limit', $limit);
            }
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $logs[] = $row;
        }

        return $logs;
    }

    /**
     *  Log a message
     */
    public function log(string $type, string $component, string $message)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO logs (Date, Time, Type, Component, Message, Status) VALUES (:date, :time, :type, :component, :message, 'new')");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':component', $component);
            $stmt->bindValue(':message', $message);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Acquit a log message
     */
    public function acquit(int $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE logs SET Status = 'acquitted' WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }
}
