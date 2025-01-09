<?php

namespace Controllers\Host\Package;

use Exception;
use DateTime;

class Package
{
    private $model;

    public function __construct(int $hostId)
    {
        $this->model = new \Models\Host\Package\Package($hostId);
    }

    /**
     *  Retrieve the list of inventoried packages on the host
     */
    public function getInventory() : array
    {
        return $this->model->getInventory();
    }

    /**
     *  Retrieve the list of installed packages on the host
     */
    public function getInstalled() : array
    {
        return $this->model->getInstalled();
    }

    /**
     *  Retrieve the list of packages available for update on the host
     *  It is possible to add an offset to the request
     */
    public function getAvailable(bool $withOffset = false, int $offset = 0) : array
    {
        return $this->model->getAvailable($withOffset, $offset);
    }

    /**
     *  Retrieve the complete history of a package (its installation, its updates, etc...)
     */
    public function getTimeline(string $packageName) : string
    {
        /**
         *  Retrieve the events of the package
         */
        $events = $this->model->getTimeline($packageName);

        ob_start();

        include_once(ROOT . '/views/includes/host/package/timeline.inc.php');

        return ob_get_clean();
    }

    /**
     *  Count the number of packages installed, updated, removed... over the last X days.
     *  Returns an array containing dates => number of packages
     *  Function used notably for creating the ChartJS 'line' chart on the host page
     */
    public function countByStatusOverDays(string $status, string $days) : array
    {
        if ($status != 'installed' and $status != 'upgraded' and $status != 'removed') {
            throw new Exception("Invalid status");
        }

        $dateEnd   = date('Y-m-d');
        $dateStart = date_create($dateEnd)->modify("-${days} days")->format('Y-m-d');

        return $this->model->countByStatusOverDays($status, $dateStart, $dateEnd);
    }
}
