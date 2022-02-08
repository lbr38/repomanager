<?php

abstract class Model {

    public $db;
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

        return;
    }

    public function closeConnection()
    {
        $this->db->close();
    }
}
?>