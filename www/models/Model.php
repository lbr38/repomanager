<?php

require_once("${WWW_DIR}/models/Connection.php");

abstract class Model {

    public $db;
    protected $host_db;

    /**
     *  Nouvelle connexion à la base de données
     */
    public function getConnection(string $database, string $mode, ?string $hostId = null)
    {
        if (!empty($hostId)) {
            $this->host_db = new Connection($database, $mode, $hostId);
            
        } else {
            $this->db = new Connection($database, $mode);
        }

        return;
    }

    public function closeConnection()
    {
        $this->db->close();
    }
}
?>