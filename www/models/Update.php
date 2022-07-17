<?php

namespace Models;

use Exception;

class Update extends Model
{
    public function __construct()
    {
        /**
         *  Open database
         */
        $this->getConnection('main');
    }

    public function updateDB(string $updateFile)
    {
        if (!file_exists($updateFile)) {
            throw new Exception("Error: database update file '$updateFile' not found");
        }

        /**
         *  Include file to execute SQL queries in it.
         */
        echo 'Executing ' . $updateFile . PHP_EOL;

        include_once($updateFile);
    }
}
