<?php

namespace Controllers\Database;

class Maintenance
{
    private $model;

    public function __construct(string $database, int|null $hostId = null)
    {
        $this->model = new \Models\Database\Maintenance($database, $hostId);
    }

    /**
     *  Perform a database VACUUM operation to clean and optimize the database
     */
    public function vacuum() : void
    {
        $this->model->vacuum();
    }

    /**
     *  Perform a database ANALYZE operation to update the database statistics
     */
    public function analyze() : void
    {
        $this->model->analyze();
    }

    /**
     *  Perform a database integrity check on the database
     */
    public function integrityCheck(): void
    {
        $this->model->integrityCheck();
    }

    public function __destruct()
    {
        $this->model->closeConnection();
    }
}
