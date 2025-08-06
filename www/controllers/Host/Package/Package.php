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
     *  Return package Id in database, based on its name and version
     */
    private function getIdByNameVersion(string $name, string $version = null) : int|bool
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

    /**
     *  Add a new package in database
     */
    private function addPackage(string $name, string $version, string $state, string $type, string $date, string $time, string $eventId = null) : void
    {
        $this->model->addPackage($name, $version, $state, $type, $date, $time, $eventId);
    }

    /**
     *  Add a package in the table available packages list
     */
    private function addPackageAvailable(string $name, string $version) : void
    {
        $this->model->addPackageAvailable($name, $version);
    }

    /**
     *  Update a package in the available packages list
     */
    private function updatePackageAvailable(string $name, string $version) : void
    {
        $this->model->updatePackageAvailable($name, $version);
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
     *  Add a new event in database
     */
    private function addEvent(string $dateStart, string $dateEnd, string $timeStart, string $timeEnd) : void
    {
        $this->model->addEvent($dateStart, $dateEnd, $timeStart, $timeEnd);
    }

    /**
     *  Return true if the package exists in database
     */
    private function exists(string $name) : bool
    {
        return $this->model->exists($name);
    }

    /**
     *  Return true if an event exists at the specified date and time
     */
    private function eventExists(string $dateStart, string $timeStart) : bool
    {
        return $this->model->eventExists($dateStart, $timeStart);
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
    public function setPackageState(string $name, string $version, string $state, string $date, string $time, string $eventId = null) : void
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
    public function setPackagesInventory(string $packagesInventory) : void
    {
        /**
         *  If the list of packages is empty, we cannot continue
         */
        if (empty($packagesInventory)) {
            throw new Exception('Packages list is empty');
        }

        /**
         *  The packages are transmitted as a string, separated by a comma. We explode this string into an array and remove empty entries
         */
        $packagesList = array_filter(explode(",", \Controllers\Common::validateData($packagesInventory)));

        if (empty($packagesList)) {
            throw new Exception('No package to process');
        }

        /**
         *  Process each package
         */
        foreach ($packagesList as $packageDetails) {
            /**
             *  Each line contains the package name, its version and its description separated by a | (ex: nginx|xxx-xxxx|nginx description)
             */
            $packageDetails = explode('|', $packageDetails);

            /**
             *  Retrieving the package name, if it is empty then we move to the next one
             */
            if (empty($packageDetails[0])) {
                continue;
            }
            $packageName = $packageDetails[0];

            /**
             *  Package version
             */
            if (!empty($packageDetails[1])) {
                $packageVersion = $packageDetails[1];
            } else {
                $packageVersion = 'unknown';
            }

            /**
             *  Insertion in database
             *  We first check if the package (its name) exists in the database
             */
            if ($this->exists($packageName) === false) {
                /**
                 *  If it does not exist, we add it to the database (otherwise we do nothing)
                 */
                $this->addPackage($packageName, $packageVersion, 'inventored', 'package', date('Y-m-d'), date('H:i:s'));
            } else {
                /**
                 *  If the package exists, we will perform different actions depending on its state in the database
                 */

                /**
                 *  First we retrieve the current state of the package in the database
                 */
                $packageState = $this->getPackageState($packageName);

                /**
                 *  If the package is in 'installed' or 'inventored' state, we do nothing
                 *
                 *  Otherwise, if the package is in 'removed' or 'upgraded' state, we update the information in the database
                 */
                if ($packageState == 'removed') {
                    /**
                     *  Adding the package in the database in 'inventored' state
                     */
                    $this->setPackageState($packageName, $packageVersion, 'inventored', date('Y-m-d'), date('H:i:s'));
                }
            }
        }
    }

    /**
     *  Update the state of available packages (to be updated) of the host in the database
     */
    public function setPackagesAvailable(string $packagesAvailable) : void
    {
        /**
         *  If the list of packages is empty, we cannot continue
         */
        if (empty($packagesAvailable)) {
            throw new Exception('Packages list is empty');
        }

        /**
         *  Two possibilities:
         *  - either we have transmitted "none", which means that there are no packages available on the host
         *  - or we have transmitted a list of packages separated by a comma
         */
        if ($packagesAvailable == 'none') {
            $packagesList = 'none';
        } else {
            /**
             *  The packages are transmitted as a string, separated by a comma. We explode this string into an array and remove empty entries
             */
            $packagesList = array_filter(explode(",", \Controllers\Common::validateData($packagesAvailable)));
        }

        if (empty($packagesList)) {
            throw new Exception('No package to process');
        }

        /**
         *  Clean the table to avoid duplicates
         */
        $this->cleanPackageAvailableTable();

        /**
         *  If the host has transmitted "none" (no package available for update) then we stop here
         */
        if ($packagesList == 'none') {
            return;
        }

        foreach ($packagesList as $packageDetails) {
            /**
             *  Each line contains the package name, its version and its description separated by a | (ex: nginx|xxx-xxxx|nginx description)
             */
            $packageDetails = explode('|', $packageDetails);

            /**
             *  Retrieving the package name, if it is empty then we move to the next one
             */
            if (empty($packageDetails[0])) {
                continue;
            }
            $packageName = $packageDetails[0];

            /**
             *  Package version
             */
            if (!empty($packageDetails[1])) {
                $packageVersion = $packageDetails[1];
            } else {
                $packageVersion = 'unknown';
            }

            /**
             *  If the package already exists in packages_available then we update it (the version may have changed)
             */
            if ($this->packageAvailableExists($packageName) === true) {
                /**
                 *  If it exists in the database, we also check the version present in the database
                 *  If the version in the database is different then we update the package in the database, otherwise we do nothing
                 */
                if ($this->packageVersionAvailableExists($packageName, $packageVersion) === true) {
                    $this->updatePackageAvailable($packageName, $packageVersion);
                }
            } else {
                /**
                 *  If the package does not exist we add it to the database
                 */
                $this->addPackageAvailable($packageName, $packageVersion);
            }
        }
    }

    /**
     *  Ajout de l'historique des évènements relatifs aux paquets (installation, mise à jour, etc...) d'un hôte en base de données
     *  Set the full history of events related to packages (installation, update, etc...) of a host in the database
     */
    public function setEventsFullHistory(array $history) : void
    {
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

        foreach ($history as $event) {
            $event->date_start;
            $event->date_end;
            $event->time_start;
            $event->time_end;

            /**
             *  Check if an event with the same date and time does not already exist, otherwise ignore it and move to the next one
             */
            if ($this->eventExists($event->date_start, $event->time_start) === true) {
                continue;
            }

            /**
             *  Add the event in the database
             */
            $this->addEvent($event->date_start, $event->date_end, $event->time_start, $event->time_end);

            /**
             *  Retrieving the Id inserted in the database
             */
            $eventId = $this->model->getHostLastInsertRowID();

            // If the event has installed packages
            if (!empty($event->installed)) {
                foreach ($event->installed as $package_installed) {
                    $this->setPackageState($package_installed->name, $package_installed->version, 'installed', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has installed dependencies
            if (!empty($event->dep_installed)) {
                foreach ($event->dep_installed as $dep_installed) {
                    $this->setPackageState($dep_installed->name, $dep_installed->version, 'dep-installed', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has updated packages
            if (!empty($event->upgraded)) {
                foreach ($event->upgraded as $package_upgraded) {
                    $this->setPackageState($package_upgraded->name, $package_upgraded->version, 'upgraded', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has uninstalled packages
            if (!empty($event->removed)) {
                foreach ($event->removed as $package_removed) {
                    $this->setPackageState($package_removed->name, $package_removed->version, 'removed', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has downgraded packages
            if (!empty($event->downgraded)) {
                foreach ($event->downgraded as $package_downgraded) {
                    $this->setPackageState($package_downgraded->name, $package_downgraded->version, 'downgraded', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has reinstalled packages
            if (!empty($event->reinstalled)) {
                foreach ($event->reinstalled as $package_reinstalled) {
                    $this->setPackageState($package_reinstalled->name, $package_reinstalled->version, 'reinstalled', $event->date_start, $event->time_start, $eventId);
                }
            }

            // If the event has purged packages
            if (!empty($event->purged)) {
                foreach ($event->purged as $package_purged) {
                    $this->setPackageState($package_purged->name, $package_purged->version, 'purged', $event->date_start, $event->time_start, $eventId);
                }
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
