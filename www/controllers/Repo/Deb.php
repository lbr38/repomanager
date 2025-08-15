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
     *  Return true if a repository with the specified name, distribution and component/section exists
     */
    public function exists(string $name, string $distribution, string $component) : bool
    {
        return $this->model->exists($name, $distribution, $component);
    }

    /**
     *  Add a new DEB repository
     */
    public function add(string $name, string $distribution, string $component, string $source = '') : void
    {
        $this->model->add($name, $distribution, $component, $source);
    }
}
