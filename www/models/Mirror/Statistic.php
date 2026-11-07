<?php

namespace Models\Mirror;

use Exception;
use Controllers\Database\Log as DbLog;

class Statistic extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('stats');
    }

    /**
     *  Return access requests of the specified mirror, most recent first
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getAccess(int $mirrorId, array $envs, int $timeStart, int $timeEnd, bool $count, bool $withOffset, int $offset): array|int
    {
        $data = [];

        try {
            $query = ($count ? "SELECT COUNT(*) as Count" : "SELECT *") . " FROM access_mirror WHERE Id_mirror = :mirrorId" . $this->envCondition($envs) . " AND Timestamp >= :timeStart AND Timestamp <= :timeEnd";

            // Id DESC is the insertion order, faster than ordering by Timestamp
            if (!$count) {
                $query .= " ORDER BY Id DESC";
            }

            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            $stmt = $this->prepare($query, $mirrorId, $envs, $timeStart, $timeEnd);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($count) {
                return $row['Count'];
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the number of access requests of the specified mirror, by environment and timestamp, for a given period
     */
    public function getAccessByPeriod(int $mirrorId, array $envs, int $timeStart, int $timeEnd): array
    {
        $data = [];

        try {
            $query = "SELECT Env, Timestamp, COUNT(*) as Count FROM access_mirror WHERE Id_mirror = :mirrorId" . $this->envCondition($envs) . " AND Timestamp >= :timeStart AND Timestamp <= :timeEnd GROUP BY Env, Timestamp ORDER BY Timestamp ASC";

            $result = $this->prepare($query, $mirrorId, $envs, $timeStart, $timeEnd)->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the number of access requests of the specified mirror, grouped by IP
     *  It is possible to count the number of unique IPs
     *  It is possible to add an offset to the request
     */
    public function getAccessByIpCount(int $mirrorId, array $envs, int $timeStart, int $timeEnd, bool $count, bool $withOffset, int $offset): array|int
    {
        $data = [];

        try {
            $query = "SELECT Source, IP, COUNT(*) as Count FROM access_mirror WHERE Id_mirror = :mirrorId" . $this->envCondition($envs) . " AND Timestamp >= :timeStart AND Timestamp <= :timeEnd GROUP BY IP";

            if ($count) {
                $query = "SELECT COUNT(*) as Count FROM (" . $query . ")";
            } else {
                $query .= " ORDER BY Count DESC";
            }

            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            $stmt = $this->prepare($query, $mirrorId, $envs, $timeStart, $timeEnd);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if ($count) {
                return $row['Count'];
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Add a mirror access request
     */
    public function addAccess(int $timestamp, int $mirrorId, string $env, string $sourceHost, string $sourceIp, string $request, string $result): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO access_mirror (Timestamp, Id_mirror, Env, Source, IP, Request, Request_result) VALUES (:timestamp, :mirrorId, :env, :sourceHost, :sourceIp, :request, :result)");
            $stmt->bindValue(':timestamp', $timestamp, SQLITE3_INTEGER);
            $stmt->bindValue(':mirrorId', $mirrorId, SQLITE3_INTEGER);
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':sourceHost', $sourceHost);
            $stmt->bindValue(':sourceIp', $sourceIp);
            $stmt->bindValue(':request', $request);
            $stmt->bindValue(':result', $result);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return the environments condition of a query (OR conditions), environments are bound as :env0, :env1...
     */
    private function envCondition(array $envs): string
    {
        if (empty($envs)) {
            return '';
        }

        $conditions = [];

        foreach (array_values($envs) as $index => $env) {
            $conditions[] = 'Env = :env' . $index;
        }

        return ' AND (' . implode(' OR ', $conditions) . ')';
    }

    /**
     *  Prepare a query and bind the common parameters
     */
    private function prepare(string $query, int $mirrorId, array $envs, int $timeStart, int $timeEnd): \SQLite3Stmt
    {
        $stmt = $this->db->prepare($query);
        $stmt->bindValue(':mirrorId', $mirrorId, SQLITE3_INTEGER);
        $stmt->bindValue(':timeStart', $timeStart, SQLITE3_INTEGER);
        $stmt->bindValue(':timeEnd', $timeEnd, SQLITE3_INTEGER);

        foreach (array_values($envs) as $index => $env) {
            $stmt->bindValue(':env' . $index, (string) $env);
        }

        return $stmt;
    }
}
