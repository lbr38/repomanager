<?php

namespace Controllers\Layout;

use Exception;
use Datetime;

class ContainerState
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Layout\ContainerState();
    }

    /**
     *  Get all layout containers state
     */
    public function get()
    {
        return $this->model->get();
    }

    /**
     *  Add a new layout container state
     */
    public function add(string $name, string $id)
    {
        $this->model->add($name, $id);
    }

    /**
     *  Update a layout container state
     */
    public function update(string $name)
    {
        /**
         *  Generate a new random Id
         */
        $id = rand(10000, 100000);

        /**
         *  Check if container name exists
         */
        if ($this->model->exists($name)) {
            $this->model->update($name, $id);
            return;
        }

        /**
         *  If not, add it
         */
        $this->add($name, $id);
    }

    /**
     *  Check if a container name exists
     */
    public function exists(string $name)
    {
        return $this->model->exists($name);
    }
}
