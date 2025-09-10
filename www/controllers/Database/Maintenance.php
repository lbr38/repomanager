<?php

namespace Controllers\Database;

use Exception;

class Maintenance
{
    private $model;

    public function __construct(string $database)
    {
        $this->model = new \Models\Database\Maintenance($database);
    }

    /**
     *  Perform a database VACUUM operation to clean and optimize the database
     */
    public function vacuum()
    {
        $this->model->vacuum();
    }

    /**
     *  Perform a database ANALYZE operation to update the database statistics
     */
    public function analyze()
    {
        $this->model->analyze();
    }

    public function __destruct()
    {
        $this->model->closeConnection();
    }
}
