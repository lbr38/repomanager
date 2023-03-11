<?php

namespace Controllers;

use Exception;

class Connection
{
    private $model;

    /**
     *  Check if database exists or generate it
     */
    public function checkDatabase(string $database)
    {
        try {
            $this->model = new \Models\Connection($database);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        unset($this->model);
    }
}
