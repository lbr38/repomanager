<?php

namespace Models\Task\Log;

use Controllers\Database\Log as DbLog;
use Exception;

class Step extends \Models\Model
{
    public function __construct(int $taskId)
    {
        $this->getConnection('task-log', $taskId);
    }

    /**
     *  Add a new step in the database
     */
    public function new(int $taskId, string $identifier, string $title) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("INSERT INTO steps ('Identifier', 'Title', 'Status', 'Start', 'Task_id') VALUES (:identifier, :title, 'running', :start, :taskId)");
            $stmt->bindValue(':identifier', $identifier);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':start', microtime(true));
            $stmt->bindValue(':taskId', $taskId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Set the latest step status
     */
    public function status(int $stepId, string $status, string $message = '') : void
    {
        try {
            $end = microtime(true);

            /**
             *  Get step start time
             */
            $stmt = $this->dedicatedDb->prepare("SELECT Start FROM steps WHERE Id = :stepId");
            $stmt->bindValue(':stepId', $stepId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $start = $row['Start'];
            }

            /**
             *  Calculate step duration
             */
            $duration = $end - $start;

            $stmt = $this->dedicatedDb->prepare("UPDATE steps SET Status = :status, End = :end, Duration = :duration, Message = :message WHERE Id = :stepId");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':end', $end);
            $stmt->bindValue(':duration', $duration);
            $stmt->bindValue(':message', $message);
            $stmt->bindValue(':stepId', $stepId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Get steps for the provided task ID
     */
    public function get(int $taskId) : array
    {
        $data = [];

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM steps WHERE Task_id = :taskId");
            $stmt->bindValue(':taskId', $taskId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data[] = $row;
            }

            return $data;
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return the latest step ID for the provided task ID
     */
    public function getLatestStepId(int $taskId) : int
    {
        $data = '';

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Id FROM steps WHERE Task_id = :taskId ORDER BY Id DESC LIMIT 1");
            $stmt->bindValue(':taskId', $taskId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $data = $row['Id'];
            }

            return $data;
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
