<?php

namespace Models;

use Exception;

abstract class Model
{
    protected $db;
    protected $dedicatedDb;

    /**
     *  Open a new connection to the database
     */
    public function getConnection(string $database, int|null $databaseId = null)
    {
        if (!empty($databaseId)) {
            $this->dedicatedDb = new Connection($database, $databaseId);
        } else {
            $this->db = new Connection($database);
        }
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
        $this->db->close();
    }
}
