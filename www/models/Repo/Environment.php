<?php

namespace Models\Repo;

use Exception;
use \Controllers\Database\Log as DbLog;

class Environment extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Associate a new env to a snapshot
     */
    public function add(string $env, string $description = null, int $snapId) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos_env ('Env', 'Description', 'Id_snap') VALUES (:env, :description, :snapId)");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Remove an environment from a snapshot
     */
    public function remove(int $id) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM repos_env WHERE Id = :envId");
            $stmt->bindValue(':envId', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update environment description
     */
    public function updateDescription(int $id, string $description) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE repos_env SET Description = :description WHERE Id = :envId");
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':envId', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return true if the repository environment Id exists
     */
    public function exists(int $id)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos_env WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }
}
