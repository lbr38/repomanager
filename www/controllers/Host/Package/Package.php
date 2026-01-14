<?php

namespace Controllers\Host\Package;

use Exception;
use \Controllers\Utils\Validate;

class Package
{
    private $hostId;
    private $model;

    public function __construct(int $hostId)
    {
        $this->hostId = $hostId;
        $this->model = new \Models\Host\Package\Package($hostId);
    }

    /**
     *  Get packages by date and state
     */
    public function getByDate(string $date, string|null $state = null) : array
    {
        return $this->model->getByDate($date, $state);
    }

    /**
     *  Return package Id in database, based on its name and version
     */
    private function getIdByNameVersion(string $name, string $version = '') : int|bool
    {
        return $this->model->getIdByNameVersion($name, $version);
    }

    /**
     *  Return an array with all the information about a package
     */
    public function getPackageInfo(string $packageId) : array
    {
        return $this->model->getPackageInfo($packageId);
    }

    /**
     *  Return the current state of a package
     */
    private function getPackageState(string $packageName, string|null $packageVersion = null) : string
    {
        return $this->model->getPackageState($packageName, $packageVersion);
    }

    /**
     *  Return the list of inventoried packages on the host
     */
    public function getInventory() : array
    {
        return $this->model->getInventory();
    }

    /**
     *  Return the list of installed packages on the host
     */
    public function getInstalled() : array
    {
        return $this->model->getInstalled();
    }

    /**
     *  Return the list of packages available for update on the host
     *  It is possible to add an offset to the request
     */
    public function getAvailable(bool $withOffset = false, int $offset = 0) : array
    {
        return $this->model->getAvailable($withOffset, $offset);
    }

    /**
     *  Return the list of packages from an event and whose package state is defined by $state (installed, upgraded, removed)
     */
    public function getEventPackagesList(int $eventId, string $state) : array
    {
        return $this->model->getEventPackagesList($eventId, $state);
    }

    /**
     *  Generate the package details of an event (installed packages, updated packages...) by date and package state
     */
    public function generateDetails(string $date) : string
    {
        // Check that the date is valid
        Validate::date($date, 'Y-m-d');

        // Get all packages for the specified date and state
        $packages = $this->getByDate($date);

        ob_start();

        include_once(ROOT . '/views/includes/host/package/event-packages-details.inc.php');

        return ob_get_clean();
    }

    /**
     *  Retrieve the complete history of a package (its installation, its updates, etc...)
     */
    public function generateTimeline(string $packageName) : string
    {
        /**
         *  Retrieve the events of the package
         */
        $events = $this->model->generateTimeline($packageName);

        ob_start();

        include_once(ROOT . '/views/includes/host/package/timeline.inc.php');

        return ob_get_clean();
    }

    /**
     *  Count the number of packages installed, updated, removed... over the last X days.
     *  Returns an array containing dates => number of packages
     */
    public function countByStatusOverDays(string $status, string $days) : array
    {
        if (!in_array($status, ['installed', 'upgraded', 'removed'])) {
            throw new Exception('invalid status: ' . $status);
        }

        $dateEnd   = date('Y-m-d');
        $dateStart = date_create($dateEnd)->modify('-' . $days . ' days')->format('Y-m-d');

        return $this->model->countByStatusOverDays($status, $dateStart, $dateEnd);
    }

    /**
     *  Add a new package in database
     */
    private function addPackage(string $name, string $version, string $state, string $type, string $date, string $time, string $eventId = '') : void
    {
        $this->model->addPackage($name, $version, $state, $type, $date, $time, $eventId);
    }

    /**
     *  Add a package in the table available packages list
     */
    private function addPackageAvailable(string $name, string $version, string $repository) : void
    {
        $this->model->addPackageAvailable($name, $version, $repository);
    }

    /**
     *  Update a package in the available packages list
     */
    private function updatePackageAvailable(string $name, string $version, string $repository) : void
    {
        $this->model->updatePackageAvailable($name, $version, $repository);
    }

    /**
     *  Delete a package from the available packages list
     */
    private function deletePackageAvailable(string $packageName, string $packageVersion) : void
    {
        $this->model->deletePackageAvailable($packageName, $packageVersion);
    }

    /**
     *  Clean packages available table
     */
    private function cleanPackageAvailableTable() : void
    {
        $this->model->cleanPackageAvailableTable();
    }

    /**
     *  Return true if the package exists in database
     */
    private function exists(string $name) : bool
    {
        return $this->model->exists($name);
    }

    /**
     *  Return true if the available package exists in database
     */
    private function packageAvailableExists(string $name) : bool
    {
        return $this->model->packageAvailableExists($name);
    }

    /**
     *  Return true if the available package and its version exists in database
     */
    private function packageVersionAvailableExists(string $name, string $version) : bool
    {
        return $this->model->packageVersionAvailableExists($name, $version);
    }

    /**
     *  Add package state in database
     */
    public function setPackageState(string $name, string $version, string $state, string $date, string $time, string $eventId = '') : void
    {
        /**
         *  If the package already exists in database, we update it
         */
        if ($this->exists($name) === true) {
            /**
             *  First we make a copy of the current state of the package in packages_history to keep track.
             *  Retrieving the package Id beforehand
             */
            $packageId = $this->getIdByNameVersion($name);

            /**
             *  Saving the current state
             */
            $this->setPackageHistory($packageId);

            /**
             *  Then we update the package state and its version in the database with the information that has been transmitted
             */
            $this->model->setPackageState($name, $version, $state, $date, $time, $eventId);
        } else {
            /**
             *  If the package does not exist, we add it directly in the specified state (installed, upgraded, removed...)
             */
            $this->model->addPackage($name, $version, $state, 'package', $date, $time, $eventId);
        }

        /**
         *  Finally, if the package and its version were present in packages_available, we remove it
         */
        $this->deletePackageAvailable($name, $version);
    }

    /**
     *  Copy the current state of a package from the packages table to the packages_history table to keep track of this state
     */
    private function setPackageHistory(string $packageId) : void
    {
        /**
         *  Retrieving all the information about the package in its current state
         */
        $data = $this->getPackageInfo($packageId);

        if (!empty($data['Name'])) {
            $packageName = $data['Name'];
        }
        if (!empty($data['Version'])) {
            $packageVersion = $data['Version'];
        }
        if (!empty($data['State'])) {
            $packageState = $data['State'];
        }
        if (!empty($data['Type'])) {
            $packageType = $data['Type'];
        }
        if (!empty($data['Date'])) {
            $packageDate = $data['Date'];
        }
        if (!empty($data['Time'])) {
            $packageTime = $data['Time'];
        }
        if (!empty($data['Id_event'])) {
            $packageEventId = $data['Id_event'];
        } else {
            $packageEventId = '';
        }

        /**
         *  Then we copy this state in the packages_history table
         */
        $this->model->setPackageHistory($packageName, $packageVersion, $packageState, $packageType, $packageDate, $packageTime, $packageEventId);
    }

    /**
     *  Update the inventory of installed packages on the host in database
     */
    public function setPackagesInventory(array $packages) : void
    {
        // If the list of packages is empty, throw an exception
        if (empty($packages)) {
            return;
        }

        foreach ($packages as $package) {
            $name = $package->name;
            $version = $package->version;

            // If the package does not exist in database, add it
            if (!$this->exists($name)) {
                $this->addPackage($name, $version, 'inventored', 'package', date('Y-m-d'), date('H:i:s'));
                continue;
            }

            /**
             *  If the package exists, perform different actions depending on its state in the database
             *  First, retrieve the current state of the package in the database
             */
            $state = $this->getPackageState($name);

            /**
             *  If the package is in 'installed' or 'inventored' state, do nothing
             *  Otherwise, if the package is in 'removed' state, update the information in the database
             */
            if ($state == 'removed') {
                $this->setPackageState($name, $version, 'inventored', date('Y-m-d'), date('H:i:s'));
            }
        }
    }

    /**
     *  Update the state of available packages (to be updated) of the host in the database
     */
    public function setPackagesAvailable(array $packages) : void
    {
        // Clean the current available packages table to avoid duplicates
        $this->cleanPackageAvailableTable();

        // If the list of packages is empty, this means there is no package available for update, we stop here
        if (empty($packages)) {
            return;
        }

        foreach ($packages as $package) {
            $name = $package->name;
            $version = $package->version;
            $repository = $package->repository;

            // If name is empty, we skip this package
            if (empty($name)) {
                continue;
            }

            // If version is empty, throw an exception
            if (empty($version)) {
                throw new Exception('Package version is empty for package: ' . $name);
            }

            // If the package does not exist in available packages list, add it
            if (!$this->packageAvailableExists($name)) {
                $this->addPackageAvailable($name, $version, $repository);
                continue;
            }

            // If it exists in the database, also check the version present in the database
            // If the version in the database is different then we update the package in the database, otherwise we do nothing
            if ($this->packageVersionAvailableExists($name, $version)) {
                $this->updatePackageAvailable($name, $version, $repository);
            }
        }
    }

    /**
     *  Search for package(s) in the database of the host
     */
    public function searchPackage(string $name, string|null $version, bool $strictName = false, $strictVersion = false) : array
    {
        return $this->model->searchPackage($name, $version, $strictName, $strictVersion);
    }
}
