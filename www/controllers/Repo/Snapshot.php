<?php

namespace Controllers\Repo;

use Exception;

class Snapshot
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Snapshot();
    }

    /**
     *  Return true if a task is queued or running for the specified snapshot
     */
    public function taskRunning(int $snapId) : bool
    {
        return $this->model->taskRunning($snapId);
    }
}
