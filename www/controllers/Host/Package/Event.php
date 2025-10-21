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
     *  Get event by its ID
     */
    public function get(int $id) : array
    {
        return $this->model->get($id);
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

    /**
     *  Generate the details of an event (installed packages, updated packages...) by date and package state
     */
    public function generateDetails(int $id) : string
    {
        $packageController = new Package($this->hostId);

        // Check that event ID is valid
        if (!$this->exists($id)) {
            throw new Exception('Event does not exist');
        }

        // Get event details
        $event = $this->get($id);

        // Get packages by state
        $installed    = $packageController->getEventPackagesList($id, 'installed');
        $depInstalled = $packageController->getEventPackagesList($id, 'dep-installed');
        $reinstalled  = $packageController->getEventPackagesList($id, 'reinstalled');
        $updated      = $packageController->getEventPackagesList($id, 'upgraded');
        $removed      = $packageController->getEventPackagesList($id, 'removed');
        $downgraded   = $packageController->getEventPackagesList($id, 'downgraded');
        $purged       = $packageController->getEventPackagesList($id, 'purged');

        ob_start();

        include_once(ROOT . '/views/includes/host/package/event-details.inc.php');

        return ob_get_clean();
    }

    /**
     *  Return true if event exists
     */
    public function exists(int $id) : bool
    {
        return $this->model->exists($id);
    }
}
