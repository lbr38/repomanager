<?php

namespace Controllers\Layout;

class ContainerReload
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Layout\ContainerReload();
    }

    /**
     *  Get all layout containers state
     */
    public function get()
    {
        return $this->model->get();
    }

    /**
     *  Add a container to reload in database
     */
    public function reload(string $name)
    {
        /**
         *  Ignore if container already exists in the database
         */
        if ($this->model->exists($name)) {
            return;
        }

        $this->model->add($name);
    }

    /**
     *  Check if a container name exists
     */
    public function exists(string $name)
    {
        return $this->model->exists($name);
    }

    /**
     *  Clean all containers entries
     */
    public function clean()
    {
        $this->model->clean();
    }
}
