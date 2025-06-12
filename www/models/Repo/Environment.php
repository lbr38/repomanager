<?php

namespace Models\Repo;

use Exception;

class Environment extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Associate a new env to a snapshot
     */
    public function add(string $env, string $description = null, int $snapId)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos_env ('Env', 'Description', 'Id_snap') VALUES (:env, :description, :snapId)");
            $stmt->bindValue(':env', $env);
            $stmt->bindValue(':description', $description);
            $stmt->bindValue(':snapId', $snapId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
