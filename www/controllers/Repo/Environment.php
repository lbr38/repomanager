<?php

namespace Controllers\Repo;

use \Controllers\Environment as GlobalEnvironment;

class Environment
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Environment();
    }

    /**
     *  Return all environments associated to a snapshot Id
     */
    public function getBySnapId(int $snapId): array
    {
        return $this->model->getBySnapId($snapId);
    }

    /**
     *  Associate a new environment to a snapshot
     */
    public function add(int $snapId, string $env) : void
    {
        $this->model->add($snapId, $env);
    }

    /**
     *  Remove an environment from a snapshot
     */
    public function remove(int $id) : void
    {
        $this->model->remove($id);
    }

    /**
     *  Return true if the repository environment Id exists
     */
    public function exists(int $id) : bool
    {
        return $this->model->exists($id);
    }
}
