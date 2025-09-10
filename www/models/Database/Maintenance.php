<?php

namespace Models\Database;

use Exception;
use SQLite3;

class Maintenance extends \Models\Model
{
    private $database;

    public function __construct(string $database)
    {
        $this->database = $database;

        $this->getConnection($database);

        // Overwrite default timeout and set a long timeout for maintenance operations, here 1 hour
        $this->db->busyTimeout(3600000);
    }

    /**
     *  Perform a database VACUUM operation to clean and optimize the database
     */
    public function vacuum()
    {
        try {
            $this->db->exec('VACUUM');
        } catch (Exception $e) {
            throw new Exception('Error while performing VACUUM on database ' . $this->database . ': ' . $e->getMessage());
        }
    }

    /**
     *  Perform a database ANALYZE operation to update the database statistics
     */
    public function analyze()
    {
        try {
            $this->db->exec('ANALYZE');
        } catch (Exception $e) {
            throw new Exception('Error while performing ANALYZE on database ' . $this->database . ': ' . $e->getMessage());
        }
    }
}
