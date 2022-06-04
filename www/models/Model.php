<?php

namespace Models;

use Exception;

abstract class Model
{
    protected $db;
    protected $host_db;

    /**
     *  Nouvelle connexion à la base de données
     */
    public function getConnection(string $database, ?string $hostId = null)
    {
        if (!empty($hostId)) {
            $this->host_db = new Connection($database, $hostId);
        } else {
            $this->db = new Connection($database);
        }
    }

    /**
     *  Retourne l'Id de la dernière ligne insérée en base de données
     */
    public function getLastInsertRowID()
    {
        return $this->db->lastInsertRowID();
    }

    public function closeConnection()
    {
        $this->db->close();
    }
}
