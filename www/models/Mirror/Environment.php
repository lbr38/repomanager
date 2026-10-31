<?php

namespace Models\Mirror;

use Exception;
use Controllers\Database\Log as DbLog;

class Environment extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return all environments associated to a snapshot Id
     */
    public function getBySnapId(int $snapId): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT Env FROM mirrors_env WHERE Id_snap = :snapId");
            $stmt->bindValue(':snapId', $snapId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row['Env'];
        }

        return $data;
    }

    /**
     *  Return environments of a mirror, as an array env => snapshot Id
     */
    public function getByMirrorId(int $mirrorId): array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT mirrors_env.Env, mirrors_env.Id_snap FROM mirrors_env
            INNER JOIN mirrors_snap ON mirrors_snap.Id = mirrors_env.Id_snap
            WHERE mirrors_snap.Id_mirror = :mirrorId");
            $stmt->bindValue(':mirrorId', $mirrorId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[$row['Env']] = $row['Id_snap'];
        }

        return $data;
    }

    /**
     *  Associate an environment to a snapshot
     */
    public function add(int $snapId, string $env): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO mirrors_env ('Env', 'Id_snap') VALUES (:env, :snapId)");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Remove an environment from all snapshots of a mirror
     */
    public function removeFromMirror(int $mirrorId, string $env): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM mirrors_env
            WHERE Env = :env
            AND Id_snap IN (SELECT Id FROM mirrors_snap WHERE Id_mirror = :mirrorId)");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':mirrorId', $mirrorId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Remove all environments associated to a snapshot Id
     */
    public function removeBySnapId(int $snapId): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM mirrors_env WHERE Id_snap = :snapId");
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
