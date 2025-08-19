<?php

namespace Controllers\Repo;

use Exception;

class Rpm extends \Controllers\Repo\Repo
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new \Models\Repo\Rpm();
    }

    /**
     *  Return the Id of a repository by its name and release version
     */
    public function getIdByNameReleasever(string $name, int $releaseVersion) : int|null
    {
        return $this->model->getIdByNameReleasever($name, $releaseVersion);
    }

    /**
     *  Return true if a repository with the specified name and release version exists
     */
    public function exists(string $name, int $releaseVersion) : bool
    {
        return $this->model->exists($name, $releaseVersion);
    }

    /**
     *  Add a new RPM repository
     */
    public function add(string $name, int $releaseVersion, string $source = '') : void
    {
        $this->model->add($name, $releaseVersion, $source);
    }
}
