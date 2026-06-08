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
    public function new(string $identifier, string $title) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO steps ('Identifier', 'Title', 'Status', 'Start') VALUES (:identifier, :title, 'running', :start)");
            $stmt->bindValue(':identifier', $identifier);
            $stmt->bindValue(':title', $title);
            $stmt->bindValue(':start', microtime(true));
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
            // Set step end time
            $end = microtime(true);

            // Get step start time
            $stmt = $this->db->prepare("SELECT Start FROM steps WHERE Id = :stepId");
            $stmt->bindValue(':stepId', $stepId);
            $result = $stmt->execute();

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $start = $row['Start'];
            }

            // Calculate step duration
            $duration = $end - $start;

            $stmt = $this->db->prepare("UPDATE steps SET Status = :status, End = :end, Duration = :duration, Message = :message WHERE Id = :stepId");
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
    public function get() : array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM steps");
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the latest step ID for the provided task ID
     */
    public function getLatestStepId() : int|null
    {
        $data = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM steps ORDER BY Id DESC LIMIT 1");
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Id'];
        }

        return $data;
    }

    /**
     *  Return true if there is at least one sub-step in warning status for the provided step ID, false otherwise
     */
    public function hasWarningSubSteps(int $stepId): bool
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM substeps WHERE Step_id = :stepId AND Status = 'warning'");
            $stmt->bindValue(':stepId', $stepId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }
}
