<?php

namespace Controllers\Repo;

use Exception;

class Deb extends \Controllers\Repo\Repo
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new \Models\Repo\Deb();
    }

    /**
     *  Return the Id of a repository by its name, distribution and component/section
     */
    public function getIdByNameDistComponent(string $name, string $distribution, string $component) : int|null
    {
        return $this->model->getIdByNameDistComponent($name, $distribution, $component);
    }

    /**
     *  Return repository environment description
     */
    public function getDescriptionByName(string $name, string $dist, string $component, string $env) : string|null
    {
        return $this->model->getDescriptionByName($name, $dist, $component, $env);
    }

    /**
     *  Return environment Id from repo name
     */
    public function getEnvIdFromRepoName(string $name, string $dist, string $component, string $env) : array
    {
        return $this->model->getEnvIdFromRepoName($name, $dist, $component, $env);
    }

    /**
     *  Return true if a repository with the specified name, distribution and component/section exists
     */
    public function exists(string $name, string $distribution, string $component) : bool
    {
        return $this->model->exists($name, $distribution, $component);
    }

    /**
     *  Return true if a snapshot exists at a specific date in database, from the repository name, version and date
     */
    public function existsSnapDate(string $name, string $dist, string $component, string $date) : bool
    {
        return $this->model->existsSnapDate($name, $dist, $component, $date);
    }

    /**
     *  Return true if a repository environment exists, based on its name and the repository name it points to
     */
    public function existsEnv(string $name, string $dist, string $component, string $env) : bool
    {
        return $this->model->existsEnv($name, $dist, $component, $env);
    }

    /**
     *  Add a new DEB repository
     */
    public function add(string $name, string $distribution, string $component, string $source = '') : void
    {
        $this->model->add($name, $distribution, $component, $source);
    }

    /**
     *  Return true if the repository exists and is active (has snapshots)
     */
    public function isActive(string $name, string $dist, string $component) : bool
    {
        return $this->model->isActive($name, $dist, $component);
    }
}
