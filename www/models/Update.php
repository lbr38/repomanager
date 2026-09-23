<?php

namespace Models;

class Update extends Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Execute migration scripts
     */
    public function migrate(string $updateFile): void
    {
        // Include file to execute SQL queries in it
        include_once($updateFile);
    }
}
