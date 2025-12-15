<?php

namespace Controllers\Repo;

use Exception;
use \Controllers\Utils\Validate;

class Environment
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Environment();
    }

    /**
     *  Associate a new environment to a snapshot
     */
    public function add(string $env, string $description = '', int $snapId) : void
    {
        $this->model->add($env, $description, $snapId);
    }

    /**
     *  Remove an environment from a snapshot
     */
    public function remove(int $id) : void
    {
        $this->model->remove($id);
    }

    /**
     *  Update environment description
     */
    public function updateDescription(int $id, string $description) : void
    {
        // Description should not contain single quotes or backslashes
        if (str_contains($description, "'") || str_contains($description, "\\") || str_contains($description, '<?') || str_contains($description, '?>')) {
            throw new Exception('Description contains invalid characters');
        }

        $this->model->updateDescription($id, Validate::string($description));
    }

    /**
     *  Return true if the repository environment Id exists
     */
    public function exists(int $id) : bool
    {
        return $this->model->exists($id);
    }
}
