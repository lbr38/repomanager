<?php

namespace Models\Repo\Statistic;

use Exception;
use \Controllers\Utils\Validate;
use \Controllers\Database\Log as DbLog;

class Rpm extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('stats');
    }

    /**
     *  Return access request of the specified rpm repository
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getAccess(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd, bool $count, bool $withOffset, int $offset): array|int
    {
        $data = [];

        try {
            // Case count is enabled
            if ($count) {
                $query = "SELECT COUNT(*) as Count";
            } else {
                $query = "SELECT *";
            }

            // Build query
            $query .= " FROM access_rpm WHERE Name = :name AND Releasever = :releasever";

            // If one or multiple env is specified (OR conditions)
            if (!empty($envs)) {
                $envConditions = [];
                foreach ($envs as $env) {
                    $envConditions[] = "Env = '" . Validate::string($env) . "'";
                }

                $query .= " AND (" . implode(" OR ", $envConditions) . ")";
            }

            $query .= " AND Timestamp >= :timeStart AND Timestamp <= :timeEnd";

            /**
             *  Invert the order of the query to get the last access logs first
             *  Order by Id DESC and not by 'Date DESC / TIME DESC' because it kills the performance
             *  Also Id DESC is accurate because it is the order of the insertion in the database (so it's like doing 'ORDER BY Date DESC / TIME DESC')
             */
            if (!$count) {
                $query .= " ORDER BY Id DESC";
            }

            // If offset is specified
            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            // Prepare query
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':timeStart', $timeStart, SQLITE3_INTEGER);
            $stmt->bindValue(':timeEnd', $timeEnd, SQLITE3_INTEGER);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            // Case count is enabled, return only the count
            if ($count) {
                return $row['Count'];
            }

            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return access request of the specified rpm repository, for a given period
     */
    public function getAccessByPeriod(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd): array|int
    {
        $data = [];

        try {
            $query = "SELECT Env, Timestamp, COUNT(*) as Count FROM access_rpm WHERE Name = :name AND Releasever = :releasever ";

            // If one or multiple env is specified (OR conditions)
            if (!empty($envs)) {
                $envConditions = [];
                foreach ($envs as $env) {
                    $envConditions[] = "Env = '" . Validate::string($env) . "'";
                }

                $query .= " AND (" . implode(" OR ", $envConditions) . ")";
            }

            $query .= " AND Timestamp >= :timeStart AND Timestamp <= :timeEnd GROUP BY Timestamp ORDER BY Timestamp ASC";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':timeStart', $timeStart, SQLITE3_INTEGER);
            $stmt->bindValue(':timeEnd', $timeEnd, SQLITE3_INTEGER);
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
     *  Return the number of access requests to the specified repository, on a given date
     */
    public function getDailyAccessCount(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd): int
    {
        $count = 0;

        try {
            $query = "SELECT COUNT(*) as Count FROM access_rpm WHERE Name = :name AND Releasever = :releasever";

            // If one or multiple env is specified (OR conditions)
            if (!empty($env)) {
                $envConditions = [];
                foreach ($envs as $env) {
                    $envConditions[] = "Env = '" . Validate::string($env) . "'";
                }

                $query .= " AND (" . implode(" OR ", $envConditions) . ")";
            }

            $query .= " AND Timestamp >= :timeStart AND Timestamp <= :timeEnd";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':timeStart', $timeStart, SQLITE3_INTEGER);
            $stmt->bindValue(':timeEnd', $timeEnd, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $count = $row['Count'];
        }

        return $count;
    }

    /**
     *  Get the total number of access requests to the specified repository, on a given date, by IP
     *  It is possible to add an offset to the request
     */
    public function getAccessByIpCount(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd, bool $count, bool $withOffset, int $offset): array|int
    {
        $data = [];

        try {
            // Case count is enabled
            if ($count) {
                $query = "SELECT *";
            } else {
                $query = "SELECT Source, IP, COUNT(*) as Count";
            }

            $query .= " FROM access_rpm WHERE Name = :name AND Releasever = :releasever";

            // If one or multiple env is specified (OR conditions)
            if (!empty($envs)) {
                $envConditions = [];
                foreach ($envs as $env) {
                    $envConditions[] = "Env = '" . Validate::string($env) . "'";
                }

                $query .= " AND (" . implode(" OR ", $envConditions) . ")";
            }

            $query .= " AND Timestamp >= :timeStart AND Timestamp <= :timeEnd GROUP BY IP";

            // Case count is enabled, wrap the query to count the number of unique IPs
            if ($count) {
                $query = "SELECT COUNT(*) as Count FROM (" . $query . ")";
            }

            if (!$count) {
                $query .= " ORDER BY Count DESC";
            }

            // If an offset is specified
            if ($withOffset) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
            $stmt->bindValue(':timeStart', $timeStart, SQLITE3_INTEGER);
            $stmt->bindValue(':timeEnd', $timeEnd, SQLITE3_INTEGER);
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
     *  Add rpm repository access log to database
     */
    public function addAccess(int $timestamp, string $name, string $releasever, string $env, string $sourceHost, string $sourceIp, string $request, string $result): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO access_rpm (Timestamp, Name, Releasever, Env, Source, IP, Request, Request_result) VALUES (:timestamp, :name, :releasever, :env, :sourceHost, :sourceIp, :request, :result)");
            $stmt->bindValue(':timestamp', $timestamp);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':releasever', $releasever);
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
}
