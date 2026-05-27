<?php

namespace Controllers\Repo;

use Exception;
use Controllers\Utils\Validate;

class Environment
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Environment();
    }

    /**
     *  Return all environments associated to a repository ID
     */
    public function getByRepoId(int $repoId): array
    {
        return $this->model->getByRepoId($repoId);
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
