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

        foreach ($events as $event) {
            $event->date_start;
            $event->date_end;
            $event->time_start;
            $event->time_end;

            // Check if an event with the same date and time does not already exist, otherwise ignore it and move to the next one
            if ($this->existsByDateTime($event->date_start, $event->time_start)) {
                continue;
            }

            // Add the event in the database
            $this->model->add($event->date_start, $event->date_end, $event->time_start, $event->time_end, $event->command);

            // Retrieving the Id inserted in the database
            $eventId = $this->model->getHostLastInsertRowID();

            // If the event has installed packages
            if (!empty($event->installed)) {
                foreach ($event->installed as $package_installed) {
                    $packageController->setPackageState($package_installed->name, $package_installed->version, 'installed', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has installed dependencies
            if (!empty($event->dep_installed)) {
                foreach ($event->dep_installed as $dep_installed) {
                    $packageController->setPackageState($dep_installed->name, $dep_installed->version, 'dep-installed', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has updated packages
            if (!empty($event->upgraded)) {
                foreach ($event->upgraded as $package_upgraded) {
                    $packageController->setPackageState($package_upgraded->name, $package_upgraded->version, 'upgraded', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has uninstalled packages
            if (!empty($event->removed)) {
                foreach ($event->removed as $package_removed) {
                    $packageController->setPackageState($package_removed->name, $package_removed->version, 'removed', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has downgraded packages
            if (!empty($event->downgraded)) {
                foreach ($event->downgraded as $package_downgraded) {
                    $packageController->setPackageState($package_downgraded->name, $package_downgraded->version, 'downgraded', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has reinstalled packages
            if (!empty($event->reinstalled)) {
                foreach ($event->reinstalled as $package_reinstalled) {
                    $packageController->setPackageState($package_reinstalled->name, $package_reinstalled->version, 'reinstalled', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has purged packages
            if (!empty($event->purged)) {
                foreach ($event->purged as $package_purged) {
                    $packageController->setPackageState($package_purged->name, $package_purged->version, 'purged', $event->date_start, $event->time_start, $eventId);
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

    /**
     *  Return true if an event exists at the specified date and time
     */
    private function existsByDateTime(string $dateStart, string $timeStart) : bool
    {
        return $this->model->existsByDateTime($dateStart, $timeStart);
    }
}
