<?php

namespace Models\Repo;

use Exception;

class Snapshot extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }
}
