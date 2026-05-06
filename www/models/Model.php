<?php

namespace Models;

abstract class Model
{
    protected $db;
    // protected $dedicatedDb;

    /**
     *  Open a new connection to the database
     */
    public function getConnection(string $database, int|null $databaseId = null)
    {
        $this->db = new Connection($database, $databaseId);
    }

    /**
     *  Return the Id of the last inserted row in the database
     */
    public function getLastInsertRowID()
    {
        return $this->db->lastInsertRowID();
    }

    public function closeConnection()
    {
        if (!is_null($this->db)) {
            $this->db->close();
        }
    }
}
