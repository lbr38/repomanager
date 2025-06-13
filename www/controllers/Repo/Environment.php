<?php

namespace Controllers\Repo;

use Exception;
use DateTime;

class Environment
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Environment();
    }

    /**
     *  Associate a new env to a snapshot
     */
    public function add(string $env, string $description = null, int $snapId)
    {
        $this->model->add($env, $description, $snapId);
    }
}
