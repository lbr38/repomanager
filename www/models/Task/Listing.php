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
    public function getQueued(bool $withNoParent, bool $withOffset, int $offset): array
    {
        $data = [];

        try {
            $query = "SELECT * FROM tasks WHERE Status = 'queued'";

            // Only tasks without a parent (default behavior)
            if ($withNoParent) {
                $query .= ' AND Parent_task_id IS NULL';
            }

            $query .= ' ORDER BY Date DESC, Time DESC';

            // Add offset if needed
            if ($withOffset === true) {
                $query .= ' LIMIT 10 OFFSET :offset';
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
     *  Get all tasks having the given status that are actually executed, ordered by execution priority
     *  Sub-tasks are included as they are the ones being executed, unlike their parent task which only groups them
     *  Scheduled tasks come first as they have a specific time to be run, and a sub-task inherits the priority of the parent task holding the schedule
     */
    public function getExecutable(string $status, bool $onlyId): array
    {
        $data = [];

        try {
            if ($onlyId) {
                $query = "SELECT tasks.Id FROM tasks";
            } else {
                $query = "SELECT tasks.* FROM tasks";
            }

            $stmt = $this->db->prepare($query . " LEFT JOIN tasks AS parent ON parent.Id = tasks.Parent_task_id
            WHERE tasks.Status = :status
            AND tasks.Id NOT IN (SELECT Parent_task_id FROM tasks WHERE Parent_task_id IS NOT NULL)
            ORDER BY (COALESCE(parent.Type, tasks.Type) = 'scheduled') DESC, tasks.Id ASC");
            $stmt->bindValue(':status', $status);
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
     *  It is possible to filter the status of task ('successful' or 'failed')
     *  It is possible to filter the tasks with search criteria (see doneCondition())
     *  It is possible to add an offset to the request
     */
    public function getDone(string $type, bool $withOffset, int $offset, string $status = '', array $search = []): array
    {
        $data = [];
        $bindings = [];

        try {
            $query = "SELECT * FROM tasks WHERE " . $this->doneCondition($status, $search, $bindings);

            // Case where we want to filter by type
            if (!empty($type)) {
                $query .= " AND Type = :type";
                $bindings[':type'] = $type;
            }

            $query .= " ORDER BY Date DESC, Time DESC";

            // Add offset if needed
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

            foreach ($bindings as $name => $value) {
                $stmt->bindValue($name, $value);
            }

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
     *  Count done tasks (with or without errors)
     *  It is possible to filter the status of task ('successful' or 'failed')
     *  It is possible to filter the tasks with search criteria (see doneCondition())
     */
    public function countDone(string $status = '', array $search = []): int
    {
        $bindings = [];

        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS Count FROM tasks WHERE " . $this->doneCondition($status, $search, $bindings));

            foreach ($bindings as $name => $value) {
                $stmt->bindValue($name, $value);
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        return (int) $result->fetchArray(SQLITE3_ASSOC)['Count'];
    }

    /**
     *  Build the SQL condition selecting done tasks (sub-tasks excluded) with the specified status and search criteria
     *  The values to bind are added to $bindings
     *
     *  Search criteria:
     *  - 'terms':  terms that must all be found in the repository targeted by the task (name, distribution, section or release version),
     *              for a task grouping sub-tasks, in the repository of one of its sub-tasks
     *  - 'id':     task Id
     *  - 'domain': 'repo' or 'mirror'
     *  - 'action': task action ('create', 'update', 'delete'...)
     */
    private function doneCondition(string $status, array $search, array &$bindings): string
    {
        // Filter by status: successful tasks are 'done', failed tasks are 'error' or 'stopped'
        if ($status == 'successful') {
            $conditions = ["Status = 'done'"];
        } elseif ($status == 'failed') {
            $conditions = ["(Status = 'error' OR Status = 'stopped')"];
        } else {
            $conditions = ["(Status = 'error' OR Status = 'done' OR Status = 'stopped')"];
        }

        $conditions[] = "Parent_task_id IS NULL";

        // JSON functions fail on malformed JSON, a task with malformed parameters is processed as a task without parameter
        $params = "CASE WHEN json_valid(tasks.Raw_params) THEN tasks.Raw_params ELSE '{}' END";

        if (!empty($search['id'])) {
            $conditions[] = "Id = :searchId";
            $bindings[':searchId'] = (int) $search['id'];
        }

        if (($search['domain'] ?? '') == 'mirror') {
            $conditions[] = "json_extract(" . $params . ", '$.domain') = 'mirror'";
        } elseif (($search['domain'] ?? '') == 'repo') {
            $conditions[] = "COALESCE(json_extract(" . $params . ", '$.domain'), '') != 'mirror'";
        }

        if (!empty($search['action'])) {
            $conditions[] = "json_extract(" . $params . ", '$.action') = :searchAction";
            $bindings[':searchAction'] = $search['action'];
        }

        if (!empty($search['terms'])) {
            $terms = array_values($search['terms']);

            foreach ($terms as $i => $term) {
                $bindings[':searchTerm' . $i] = '%' . addcslashes($term, '%_\\') . '%';
            }

            // Condition matching all the terms against the repository described by some task parameters
            $termsMatch = function (string $params) use ($terms): string {
                $fields = [];
                $match = [];

                foreach (['name', 'dist', 'section', 'releasever'] as $field) {
                    $fields[] = "COALESCE(json_extract(" . $params . ", '$.\"repo-info\".\"" . $field . "\"'), '')";
                }

                foreach (array_keys($terms) as $i) {
                    $match[] = "(" . implode(" || ' ' || ", $fields) . ") LIKE :searchTerm" . $i . " ESCAPE '\\'";
                }

                return implode(' AND ', $match);
            };

            /**
             *  The task itself targets the repository, or one of its sub-tasks does, whether they have been
             *  executed (sub-tasks stored in database) or not (sub-tasks only described in the parameters)
             */
            $conditions[] = "(" . $termsMatch($params) . "
            OR EXISTS (SELECT 1 FROM tasks AS sub WHERE sub.Parent_task_id = tasks.Id AND " . $termsMatch("CASE WHEN json_valid(sub.Raw_params) THEN sub.Raw_params ELSE '{}' END") . ")
            OR EXISTS (SELECT 1 FROM json_each(" . $params . ", '$.tasks') AS sub WHERE " . $termsMatch('sub.value') . "))";
        }

        return implode(' AND ', $conditions);
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
     *  A sub-task that has been relaunched is replaced by its relaunch, so it is not counted
     */
    public function countStatusByParentId(int $parentTaskId): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT Status, COUNT(*) AS Total FROM tasks
            WHERE Parent_task_id = :parentTaskId
            AND Id NOT IN (SELECT Relaunched_from_task_id FROM tasks WHERE Relaunched_from_task_id IS NOT NULL)
            GROUP BY Status");
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
     *  A sub-task that has been relaunched is replaced by its relaunch, so it is not counted
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
            AND Id NOT IN (SELECT Relaunched_from_task_id FROM tasks WHERE Relaunched_from_task_id IS NOT NULL)
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
