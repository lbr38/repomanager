<?php

namespace Controllers\Host\Package;

use Exception;

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
     *  Retrieves the details of an event for a specific type of packages (installed, updated, etc...)
     *  This function is triggered when hovering over a line in the event history
     */
    public function getDetails(string $eventId, string $packageState) : string
    {
        $packageState = \Controllers\Common::validateData($packageState);

        /**
         *  Retrieve the details of the event
         */
        $packages = $this->model->getDetails($eventId, $packageState);

        if ($packageState == 'installed') {
            $title = 'INSTALLED';
            $icon = 'check.svg';
        }
        if ($packageState == 'reinstalled') {
            $title = 'REINSTALLED';
            $icon = 'check.svg';
        }
        if ($packageState == 'dep-installed') {
            $title = 'DEPENDENCIES INSTALLED';
            $icon = 'check.svg';
        }
        if ($packageState == 'upgraded') {
            $title = 'UPDATED';
            $icon = 'update-yellow.svg';
        }
        if ($packageState == 'removed') {
            $title = 'UNINSTALLED';
            $icon = 'error.svg';
        }
        if ($packageState == 'purged') {
            $title = 'PURGED';
            $icon = 'error.svg';
        }
        if ($packageState == 'downgraded') {
            $title = 'DOWNGRADED';
            $icon = 'rollback.svg';
        }

        ob_start();

        include_once(ROOT . '/views/includes/host/package/event-details.inc.php');

        return ob_get_clean();
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
