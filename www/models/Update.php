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
        /**
         *  Include file to execute SQL queries in it.
         */
        include_once($updateFile);
    }
}
