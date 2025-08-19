<?php

namespace Models\Repo;

use Exception;

class Snapshot extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the list of unused snapshots for the specified repo Id and retention parameter
     */
    public function getUnused(string $repoId, string $retention) : array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT
            repos_snap.Id AS snapId,
            repos_snap.Date
            FROM repos
            LEFT JOIN repos_snap
                ON repos_snap.Id_repo = repos.Id
            LEFT JOIN repos_env
                ON repos_env.Id_snap = repos_snap.Id
            WHERE repos_snap.Id_repo = :repoId
            AND repos_env.Id_snap IS NULL
            AND repos_snap.Status = 'active'
            ORDER BY Date DESC LIMIT -1 OFFSET :retention");
            $stmt->bindValue(':repoId', $repoId);
            $stmt->bindValue(':retention', $retention);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Update snapshot status in the database
     */
    public function updateStatus(string $snapId, string $status) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_snap SET Status = :status WHERE Id = :snapId");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Return true if a snapshot with the specified ID exists
     */
    public function exists(int $id) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_snap WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if a task is queued or running for the specified snapshot
     */
    public function taskRunning(int $snapId) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM tasks
            WHERE json_extract(COALESCE(Raw_params, '{}'), '$.snap-id') == :snapId
            AND Status IN ('queued', 'running')");
            $stmt->bindValue(':snapId', strval($snapId));
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }
}
