<?php

namespace Models\Task\Log;

use Controllers\Database\Log as DbLog;
use Exception;

class Log extends \Models\Model
{
    public function __construct(int $taskId)
    {
        $this->getConnection('task-log', $taskId);
    }

    /**
     *  Get log content from the database for the provided task ID
     */
    public function getContent(int $taskId) : string
    {
        $data = '';

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Log FROM logs WHERE Task_id = :taskId");
            $stmt->bindValue(':taskId', $taskId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data = $row['Log'];
            }

            return $data;
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
