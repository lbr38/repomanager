<?php

namespace Models\Repo\Statistic;

use Exception;
use Controllers\Database\Log as DbLog;

class Statistic extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('stats');
    }

    /**
     *  Return the statistics for a given repository ID
     */
    public function getByRepoId(int $repoId): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM repo_stats WHERE Id_repo = :repoId ORDER BY Timestamp ASC");
            $stmt->bindValue(':repoId', $repoId);
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
     *  Return access queue
     */
    public function getAccessQueue(): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM access_queue LIMIT 100");
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
     *  Add statistics to database
     */
    public function add(int $timestamp, string $snapshotDate, int $snapshotSize, int $snapshotPackagesCount, int $repoId): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repo_stats ('Timestamp', 'Snapshot_date', 'Snapshot_size', 'Snapshot_packages_count', 'Id_repo') VALUES (:timestamp, :snapshotDate, :snapshotSize, :snapshotPackagesCount, :repoId)");
            $stmt->bindValue(':timestamp', $timestamp);
            $stmt->bindValue(':snapshotDate', $snapshotDate);
            $stmt->bindValue(':snapshotSize', $snapshotSize);
            $stmt->bindValue(':snapshotPackagesCount', $snapshotPackagesCount);
            $stmt->bindValue(':repoId', $repoId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Add new repository access log to queue
     */
    public function addAccessToQueue(string $request): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO access_queue (Request) VALUES (:request)");
            $stmt->bindValue(':request', $request);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Clean stats older than the specified timestamp
     */
    public function clean(int $timestamp): void
    {
        try {
            // Use a transaction to group all the delete operations
            $this->db->exec("BEGIN TRANSACTION");
            $stmt = $this->db->prepare("DELETE FROM repo_stats WHERE Timestamp <= :timestamp");
            $stmt->bindValue(':timestamp', $timestamp, SQLITE3_INTEGER);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM access_deb WHERE Timestamp <= :timestamp");
            $stmt->bindValue(':timestamp', $timestamp, SQLITE3_INTEGER);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM access_rpm WHERE Timestamp <= :timestamp");
            $stmt->bindValue(':timestamp', $timestamp, SQLITE3_INTEGER);
            $stmt->execute();

            // Commit the transaction to apply all the delete operations
            $this->db->exec("COMMIT");
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete access log from queue
     */
    public function deleteFromQueue(int $id): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM access_queue WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
