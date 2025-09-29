<?php

namespace Models\Database;

use Exception;

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
    public function vacuum() : void
    {
        try {
            // Use disk for temporary storage during VACUUM, this is to avoid using too much memory for large databases
            $this->db->exec('PRAGMA temp_store = FILE');

            // Set /tmp as the directory for temporary files
            $this->db->exec("PRAGMA temp_store_directory = '/tmp'");

            // Perform the VACUUM operation
            $this->db->exec('VACUUM');
        } catch (Exception $e) {
            throw new Exception('Error while performing VACUUM on database ' . $this->database . ': ' . $e->getMessage());
        } finally {
            // Set temp_store back to default
            $this->db->exec('PRAGMA temp_store = DEFAULT');
        }
    }

    /**
     *  Perform a database ANALYZE operation to update the database statistics
     */
    public function analyze() : void
    {
        try {
            $this->db->exec('ANALYZE');
        } catch (Exception $e) {
            throw new Exception('Error while performing ANALYZE on database ' . $this->database . ': ' . $e->getMessage());
        }
    }

    /**
     *  Perform a database integrity check on the database
     */
    public function integrityCheck(): void
    {
        try {
            $this->db->exec('PRAGMA integrity_check');
        } catch (Exception $e) {
            throw new Exception('Error while performing integrity check on database ' . $this->database . ': ' . $e->getMessage());
        }
    }
}
