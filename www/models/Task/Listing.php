<?php

namespace Models\Task;

use Exception;
use Controllers\Database\Log as DbLog;

class Listing extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get all tasks
     */
    public function get(): array
    {
        $data = [];

        try {
            $result = $this->db->query("SELECT * FROM tasks");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Get all newest tasks
     *  It is possible to add an offset to the request
     */
    public function getQueued(string $type, bool $withOffset, int $offset): array
    {
        $data = [];

        try {
            // Case where we want all types
            if (empty($type)) {
                $query = "SELECT * FROM tasks
                WHERE Status = 'queued'
                AND Parent_task_id IS NULL
                ORDER BY Date DESC, Time DESC";
            }

            // Case where we want to filter by type
            if (!empty($type)) {
                $query = "SELECT * FROM tasks
                WHERE Type = :type
                AND Status = 'queued'
                AND Parent_task_id IS NULL
                ORDER BY Date DESC, Time DESC";
            }

            // Add offset if needed
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
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
     *  Get all running tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function getRunning(string $type, bool $withOffset, int $offset): array
    {
        $data = [];

        // Case where we want all types
        try {
            if (empty($type)) {
                $query = "SELECT * FROM tasks
                WHERE Status = 'running'
                AND Parent_task_id IS NULL
                ORDER BY Date DESC, Time DESC";

            // Case where we want to filter by task type only
            } else {
                $query = "SELECT * FROM tasks
                WHERE Status = 'running' and Type = :type
                AND Parent_task_id IS NULL
                ORDER BY Date DESC, Time DESC";
            }

            // Add offset if needed
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

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
     *  Get all scheduled tasks
     *  It is possible to add an offset to the request
     */
    public function getScheduled(bool $withOffset, int $offset): array
    {
        $data = [];

        try {
            $query = "SELECT * FROM tasks
            WHERE TYPE = 'scheduled'
            AND (Status = 'scheduled'
            OR Status = 'disabled')
            ORDER BY json_extract(Raw_params, '$.schedule.schedule-date') DESC, json_extract(Raw_params, '$.schedule.schedule-time') DESC";

            // Add offset if needed
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
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
     *  Get all done tasks (with or without errors)
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function getDone(string $type, bool $withOffset, int $offset): array
    {
        $data = [];

        try {
            // Case where we want all types
            if (empty($type)) {
                $query = "SELECT * FROM tasks
                WHERE (Status = 'error'
                OR Status = 'done'
                OR Status = 'stopped')
                AND Parent_task_id IS NULL
                ORDER BY Date DESC, Time DESC";
            }

            // Case where we want to filter by type
            if (!empty($type)) {
                $query = "SELECT * FROM tasks
                WHERE Type = :type
                AND (Status = 'error'
                OR Status = 'done'
                OR Status = 'stopped')
                AND Parent_task_id IS NULL
                ORDER BY Date DESC, Time DESC";
            }

            // Add offset if needed
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
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
     *  Get all sub-tasks generated by a parent task
     *  (e.g. a scheduled task targeting a group of repositories generates one task per repository)
     *  It is possible to add an offset to the request
     */
    public function getByParentId(int $parentTaskId, bool $withOffset = false, int $offset = 0): array
    {
        $data = [];

        try {
            $query = "SELECT * FROM tasks
            WHERE Parent_task_id = :parentTaskId
            ORDER BY Date DESC, Time DESC, Id ASC";

            // Add offset if needed
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':parentTaskId', $parentTaskId, SQLITE3_INTEGER);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
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
     *  Count sub-tasks generated by a parent task, grouped by status
     */
    public function countStatusByParentId(int $parentTaskId): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT Status, COUNT(*) AS Total FROM tasks WHERE Parent_task_id = :parentTaskId GROUP BY Status");
            $stmt->bindValue(':parentTaskId', $parentTaskId, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[$row['Status']] = (int) $row['Total'];
        }

        return $data;
    }

    /**
     *  Count sub-tasks generated by multiple parent tasks, grouped by parent task Id and status
     */
    public function countStatusByParentIds(array $parentTaskIds): array
    {
        $data = [];

        // Ids are casted to int as they are directly injected into the query
        $parentTaskIds = array_map('intval', array_filter($parentTaskIds, 'is_numeric'));

        if (empty($parentTaskIds)) {
            return $data;
        }

        try {
            $result = $this->db->query("SELECT Parent_task_id, Status, COUNT(*) AS Total FROM tasks
            WHERE Parent_task_id IN (" . implode(',', $parentTaskIds) . ")
            GROUP BY Parent_task_id, Status");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[$row['Parent_task_id']][$row['Status']] = (int) $row['Total'];
        }

        return $data;
    }
}
