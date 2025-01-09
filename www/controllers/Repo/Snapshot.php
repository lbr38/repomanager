<?php

namespace Controllers\Repo;

use Exception;

class Snapshot
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Snapshot();
    }
}
