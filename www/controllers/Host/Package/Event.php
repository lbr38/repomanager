<?php

namespace Controllers\Host\Package;

use Exception;
use Controllers\Utils\Validate;

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
     *  Add events to the database
     */
    public function setHistory(array $events) : void
    {
        $packageController = new Package($this->hostId);

        /**
         *  Each event consists of a start and end date and time
         *  Then a list of installed, updated/upgraded or uninstalled packages...
         *  Example:
         *  "date_start":"2021-12-07",
         *  "date_end":"2021-12-07",
         *  "time_start":"17:32:45",
         *  "time_end":"17:34:49",
         *  "upgraded":[
         *    {
         *      "name":"bluez",
         *      "version":"5.48-0ubuntu3.5"
         *    }
         *  ]
         */

        // Map the keys sent by the host to the package states stored in database
        $states = [
            'installed'     => 'installed',
            'dep_installed' => 'dep-installed',
            'upgraded'      => 'upgraded',
            'removed'       => 'removed',
            'downgraded'    => 'downgraded',
            'reinstalled'   => 'reinstalled',
            'purged'        => 'purged'
        ];

        foreach ($events as $event) {
            // Check the event date and time format, they are stored and printed as is
            Validate::date($event['date_start'] ?? '', 'Y-m-d');
            Validate::date($event['time_start'] ?? '', 'H:i:s');

            if (!empty($event['date_end'])) {
                Validate::date($event['date_end'], 'Y-m-d');
            }

            if (!empty($event['time_end'])) {
                Validate::date($event['time_end'], 'H:i:s');
            }

            // Check if an event with the same date and time does not already exist, otherwise ignore it and move to the next one
            if ($this->existsByDateTime($event['date_start'], $event['time_start'])) {
                continue;
            }

            // Add the event in the database
            $this->model->add($event['date_start'], $event['date_end'], $event['time_start'], $event['time_end'], $event['command']);

            // Retrieving the Id inserted in the database
            $eventId = $this->model->getHostLastInsertRowID();

            // Add each package of the event with its state (installed, upgraded, removed...)
            foreach ($states as $key => $state) {
                if (empty($event[$key])) {
                    continue;
                }

                foreach ($event[$key] as $package) {
                    // Sanitize package name and version, as done in setPackagesInventory() and setPackagesAvailable()
                    $name    = Validate::string($package['name']);
                    $version = Validate::string($package['version']);

                    $packageController->setPackageState($name, $version, $state, $event['date_start'], $event['time_start'], $eventId);
                }
            }
        }
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

        include_once(ROOT . '/views/includes/modals/host/package/event-details.inc.php');

        return ob_get_clean();
    }

    /**
     *  Return true if event exists
     */
    public function exists(int $id) : bool
    {
        return $this->model->exists($id);
    }

    /**
     *  Return true if an event exists at the specified date and time
     */
    private function existsByDateTime(string $dateStart, string $timeStart) : bool
    {
        return $this->model->existsByDateTime($dateStart, $timeStart);
    }
}
