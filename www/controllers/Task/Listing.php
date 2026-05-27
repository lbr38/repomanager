<?php

namespace Controllers\Task;

use Exception;

class Listing
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Task\Listing();
    }

    /**
     *  Get all tasks
     */
    public function get(): array
    {
        return $this->model->get();
    }

    /**
     *  Get all queued tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function getQueued(string $type = '', bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getQueued($type, $withOffset, $offset);
    }

    /**
     *  Get all running tasks
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function getRunning(string $type = '', bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getRunning($type, $withOffset, $offset);
    }

    /**
     *  Get all scheduled tasks
     *  It is possible to add an offset to the request
     */
    public function getScheduled(bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getScheduled($withOffset, $offset);
    }

    /**
     *  Get all done tasks (with or without errors)
     *  It is possible to filter the type of task ('immediate' or 'scheduled')
     *  It is possible to add an offset to the request
     */
    public function getDone(string $type = 'immediate', bool $withOffset = false, int $offset = 0): array
    {
        return $this->model->getDone($type, $withOffset, $offset);
    }
}
