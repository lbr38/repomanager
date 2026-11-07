<?php

namespace Controllers\History;

use Exception;

class History
{
    protected $model;
    private $username;

    public function __construct()
    {
        $this->model = new \Models\History\History();
    }

    /**
     *  Retrieve all history
     *  It is possible to add an offset to the request
     */
    public function getAll(bool $withOffset = false, int $offset = 0)
    {
        return $this->model->getAll($withOffset, $offset);
    }

    /**
     *  Retrieve all history from a user
     *  It is possible to add an offset to the request
     */
    public function getByUserId(int $id, bool $withOffset = false, int $offset = 0)
    {
        return $this->model->getByUserId($id, $withOffset, $offset);
    }

    /**
     *  Return the number of failed local authentications for the specified username, from the current IP address,
     *  during the last specified minutes
     */
    public function countFailedLogins(string $username, int $minutes) : int
    {
        $since = time() - ($minutes * 60);

        return $this->model->countFailedLogins($username, $_SERVER['REMOTE_ADDR'] ?? '', date('Y-m-d', $since), date('H:i:s', $since));
    }

    /**
     *  Cleanup old history lines
     */
    public function cleanup(int $days) : void
    {
        $this->model->cleanup(date('Y-m-d', strtotime('-' . $days . ' days')));
    }
}
