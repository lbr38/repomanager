<?php

namespace Models\Mirror;

use Exception;
use Controllers\Database\Log as DbLog;

class Snapshot extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return snapshot details by Id
     */
    public function getById(int $id): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM mirrors_snap WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return all active snapshots of a mirror, most recent first
     */
    public function getByMirrorId(int $mirrorId): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * FROM mirrors_snap
            WHERE Id_mirror = :mirrorId
            AND Status = 'active'
            ORDER BY Date DESC, Time DESC");
            $stmt->bindValue(':mirrorId', $mirrorId);
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
     *  Return snapshots of a mirror that are not used by any environment, except the $retention most recent ones
     */
    public function getUnused(int $mirrorId, int $retention): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT mirrors_snap.* FROM mirrors_snap
            LEFT JOIN mirrors_env ON mirrors_env.Id_snap = mirrors_snap.Id
            WHERE mirrors_snap.Id_mirror = :mirrorId
            AND mirrors_snap.Status = 'active'
            AND mirrors_env.Id_snap IS NULL
            ORDER BY mirrors_snap.Date DESC, mirrors_snap.Time DESC
            LIMIT -1 OFFSET :retention");
            $stmt->bindValue(':mirrorId', $mirrorId);
            $stmt->bindValue(':retention', $retention);
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
     *  Add a snapshot in database
     */
    public function add(string $date, string $time, string $status, int $mirrorId): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO mirrors_snap ('Date', 'Time', 'Status', 'Id_mirror') VALUES (:date, :time, :status, :mirrorId)");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':mirrorId', $mirrorId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete a snapshot and its environments from the database
     */
    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM mirrors_env WHERE Id_snap = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();

            $stmt = $this->db->prepare("DELETE FROM mirrors_snap WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update snapshot size (in bytes)
     */
    public function updateSize(int $id, int $size): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE mirrors_snap SET Size = :size WHERE Id = :id");
            $stmt->bindValue(':size', $size);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
