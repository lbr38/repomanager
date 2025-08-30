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
    public function getIdByNameReleasever(string $name, string $releaseVersion) : int|null
    {
        return $this->model->getIdByNameReleasever($name, $releaseVersion);
    }

    /**
     *  Return repository environment description
     */
    public function getDescriptionByName(string $name, string $releaseVersion, string $env) : string|null
    {
        return $this->model->getDescriptionByName($name, $releaseVersion, $env);
    }

    /**
     *  Return environment Id from repo name
     */
    public function getEnvIdFromRepoName(string $name, string $releaseVersion, string $env) : array
    {
        return $this->model->getEnvIdFromRepoName($name, $releaseVersion, $env);
    }

    /**
     *  Return true if a repository with the specified name and release version exists
     */
    public function exists(string $name, string $releaseVersion) : bool
    {
        return $this->model->exists($name, $releaseVersion);
    }

    /**
     *  Return true if a snapshot exists at a specific date in database, from the repository name, version and date
     */
    public function existsSnapDate(string $name, string $releaseVersion, string $date) : bool
    {
        return $this->model->existsSnapDate($name, $releaseVersion, $date);
    }

    /**
     *  Return true if a repository environment exists, based on its name and the repository name it points to
     */
    public function existsEnv(string $name, string $releaseVersion, string $env) : bool
    {
        return $this->model->existsEnv($name, $releaseVersion, $env);
    }

    /**
     *  Add a new RPM repository
     */
    public function add(string $name, string $releaseVersion, string $source = '') : void
    {
        $this->model->add($name, $releaseVersion, $source);
    }

    /**
     *  Return true if the repository exists and is active (has snapshots)
     */
    public function isActive(string $name, string $releaseVersion) : bool
    {
        return $this->model->isActive($name, $releaseVersion);
    }
}
