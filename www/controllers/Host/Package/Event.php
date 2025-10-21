<?php

namespace Controllers\Host\Package;

class Event
{
    private $hostId;
    private $model;

    public function __construct(int $hostId)
    {
        $this->hostId = $hostId;
        $this->model = new \Models\Host\Package\Event($hostId);
    }

    /**
     *  Get list of all events date
     *  It is possible to add an offset to the request
     */
    public function getDates(bool $withOffset = false, int $offset = 0) : array
    {
        return $this->model->getDates($withOffset, $offset);
    }

    /**
     *  Retrieve informations about all actions performed on host packages (install, update, remove...)
     *  It is possible to add an offset to the request
     */
    public function getHistory(bool $withOffset = false, int $offset = 0) : array
    {
        return $this->model->getHistory($withOffset, $offset);
    }
}
