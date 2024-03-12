<?php

namespace Controllers\Task\Pool;

use Exception;

class Pool
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Task\Pool\Pool();
    }

    /**
     *  Get pool details by Id
     */
    public function getById(int $id)
    {
        return $this->model->getById($id);
    }

    /**
     *  Create a new task in task pool and return the pool Id
     */
    public function new(array $params)
    {
        return $this->model->new($params);
    }
}   
