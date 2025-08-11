<?php

namespace Models\Host\Package;

use Exception;

class Package extends \Models\Model
{
    public function __construct(int $hostId)
    {
        // Open database
        $this->getConnection('host', $hostId);
    }

    /**
     *  Return package Id in database, based on its name and version
     */
    public function getIdByNameVersion(string $packageName, string|null $packageVersion) : int|bool
    {
        /**
         *  Retrieve from name and version if both have been provided
         */
        if (!empty($packageName) and !empty($packageVersion)) {
            try {
                $stmt = $this->dedicatedDb->prepare("SELECT Id FROM packages WHERE Name = :name and Version = :version");
                $stmt->bindValue(':name', $packageName);
                $stmt->bindValue(':version', $packageVersion);
                $result = $stmt->execute();
            } catch (Exception $e) {
                $this->dedicatedDb->logError($e);
            }

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                return $row['Id'];
            }

        /**
         *  Otherwise, if we only provided the name of the package to search
         */
        } elseif (!empty($packageName)) {
            try {
                $stmt = $this->dedicatedDb->prepare("SELECT Id FROM packages WHERE Name = :name");
                $stmt->bindValue(':name', $packageName);
                $result = $stmt->execute();
            } catch (Exception $e) {
                $this->dedicatedDb->logError($e);
            }

            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                return $row['Id'];
            }
        }

        return false;
    }

    /**
     *  Return an array with all the information about a package
     */
    public function getPackageInfo(string $packageId) : array
    {
        $data = array();

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM packages WHERE Id = :packageId");
            $stmt->bindValue(':packageId', $packageId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return the current state of a package
     */
    public function getPackageState(string $packageName, string|null $packageVersion) : string
    {
        try {
            /**
             *  Case where a version number has been specified
             */
            if (!empty($packageVersion)) {
                $stmt = $this->dedicatedDb->prepare("SELECT State FROM packages WHERE Name = :name and Version = :version");
                $stmt->bindValue(':version', $packageVersion);

            /**
             *  Case where no version number has been specified
             */
            } else {
                $stmt = $this->dedicatedDb->prepare("SELECT State FROM packages WHERE Name = :name");
            }

            $stmt->bindValue(':name', $packageName);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $state = $row['State'];
        }

        return $state;
    }

    /**
     *  Return the list of inventoried packages in database
     */
    public function getInventory() : array
    {
        $datas = array();

        try {
            $result = $this->dedicatedDb->query("SELECT * FROM packages ORDER BY Name ASC");
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Return the list of installed packages in database
     */
    public function getInstalled() : array
    {
        $datas = array();

        try {
            $result = $this->dedicatedDb->query("SELECT * FROM packages WHERE State = 'inventored' or State = 'installed' or State = 'dep-installed' or State = 'upgraded' or State = 'downgraded'");
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Return the list of packages available for update in database
     *  It is possible to add an offset to the request
     */
    public function getAvailable(bool $withOffset, int $offset) : array
    {
        $data = array();

        try {
            $query = "SELECT * FROM packages_available";

            /**
             *  Add offset if needed
             */
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            /**
             *  Prepare query
             */
            $stmt = $this->dedicatedDb->prepare($query);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);

            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the list of packages from an event and whose package state is defined by $state (installed, upgraded, removed)
     *  The information is retrieved both from the packages table and from packages_history
     */
    public function getEventPackagesList(int $eventId, string $state) : array
    {
        $data = array();

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM packages
            WHERE Id_event = :eventId and State = :state
            UNION
            SELECT * FROM packages_history       
            WHERE Id_event = :eventId and State = :state");
            $stmt->bindValue(':eventId', $eventId);
            $stmt->bindValue(':state', $state);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Retrieve the complete history of a package (its installation, its updates, etc...)
     */
    public function getTimeline(string $package) : array
    {
        $events = array();

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM packages_history
            WHERE Name = :package
            UNION SELECT * FROM packages
            WHERE Name = :package
            ORDER BY Date DESC, Time DESC");
            $stmt->bindValue(':package', $package);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $events[] = $row;
        }

        return $events;
    }

    /**
     *  Return the last insert row ID in the dedicated database for a host
     */
    public function getHostLastInsertRowID()
    {
        return $this->dedicatedDb->lastInsertRowID();
    }

    /**
     *  Add package state in database
     */
    public function setPackageState(string $name, string $version, string $state, string $date, string $time, string|null $id_event) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("UPDATE packages SET Version = :version, Date = :date, Time = :time, State = :state, Id_event = :id_event WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':state', $state);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':id_event', $id_event);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Copy the current state of a package from the packages table to the packages_history table to keep track of this state
     */
    public function setPackageHistory(string $packageName, string $packageVersion, string $packageState, string $packageType, string $packageDate, string $packageTime, string $eventId) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("INSERT INTO packages_history ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, :state, :type, :date, :time, :id_event)");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $stmt->bindValue(':state', $packageState);
            $stmt->bindValue(':type', $packageType);
            $stmt->bindValue(':date', $packageDate);
            $stmt->bindValue(':time', $packageTime);
            $stmt->bindValue(':id_event', $eventId);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Search for package(s) in the database of the host
     */
    public function searchPackage(string $name, string|null $version, bool $strictName, bool $strictVersion) : array
    {
        $packages = array();

        try {
            // If strict mode is enabled, we search for the exact package name
            if ($strictName) {
                $query = "SELECT Name, Version FROM packages WHERE Name = :name";
            }

            // If strict mode is disabled, we search for packages containing the specified name
            if (!$strictName) {
                $query = "SELECT Name, Version FROM packages WHERE Name LIKE :name";
            }

            if (!empty($version)) {
                // If strict mode is enabled, we search for the exact package version
                if ($strictVersion) {
                    $query .= " and Version = :version";
                }

                // If strict mode is disabled, we search for packages containing the specified version
                if (!$strictVersion) {
                    $query .= " and Version LIKE :version";
                }
            }

            // Prepare the query
            $stmt = $this->dedicatedDb->prepare($query);

            if ($strictName) {
                $stmt->bindValue(':name', $name);
            } else {
                $stmt->bindValue(':name', $name . '%');
            }

            if (!empty($version)) {
                if ($strictVersion) {
                    $stmt->bindValue(':version', $version);
                } else {
                    $stmt->bindValue(':version', $version . '%');
                }
            }

            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $packageName = $row['Name'];
            $packageVersion = $row['Version'];
            $packages[$packageName] = $packageVersion;
        }

        return $packages;
    }

    /**
     *  Add a new package in database
     */
    public function addPackage(string $name, string $version, string $state, string $type, string $date, string $time, string|null $eventId) : void
    {
        try {
            if (!empty($eventId)) {
                $stmt = $this->dedicatedDb->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time', 'Id_event') VALUES (:name, :version, :state, :type, :date, :time, :id_event)");
                $stmt->bindValue(':id_event', $eventId);
            } else {
                $stmt = $this->dedicatedDb->prepare("INSERT INTO packages ('Name', 'Version', 'State', 'Type', 'Date', 'Time') VALUES (:name, :version, 'inventored', 'package', :date, :time)");
            }
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->bindValue(':state', $state);
            $stmt->bindValue(':type', $type);
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Add a package in the table available packages list
     */
    public function addPackageAvailable(string $name, string $version) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("INSERT INTO packages_available ('Name', 'Version') VALUES (:name, :version)");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Update a package in the available packages list
     */
    public function updatePackageAvailable(string $name, string $version) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("UPDATE packages_available SET Version = :version WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Delete a package from the available packages list
     */
    public function deletePackageAvailable(string $packageName, string $packageVersion) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("DELETE FROM packages_available WHERE Name = :name and Version = :version");
            $stmt->bindValue(':name', $packageName);
            $stmt->bindValue(':version', $packageVersion);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Clean packages available table
     */
    public function cleanPackageAvailableTable() : void
    {
        $this->dedicatedDb->exec("DELETE FROM packages_available");
        $this->dedicatedDb->exec("VACUUM");
    }

    /**
     *  Add a new event in database
     */
    public function addEvent(string $dateStart, string $dateEnd, string $timeStart, string $timeEnd) : void
    {
        try {
            $stmt = $this->dedicatedDb->prepare("INSERT INTO events ('Date', 'Date_end', 'Time', 'Time_end', 'Status') VALUES (:date_start, :date_end, :time_start, :time_end, 'done')");
            $stmt->bindValue(':date_start', $dateStart);
            $stmt->bindValue(':date_end', $dateEnd);
            $stmt->bindValue(':time_start', $timeStart);
            $stmt->bindValue(':time_end', $timeEnd);
            $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }
    }

    /**
     *  Return true if the package exists in database
     */
    public function exists(string $name) : bool
    {
        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM packages WHERE Name = :name");
            $stmt->bindValue(':name', \Controllers\Common::validateData($name));
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        if ($this->dedicatedDb->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if an event exists at the specified date and time
     */
    public function eventExists(string $dateStart, string $timeStart) : bool
    {
        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Id FROM events WHERE Date = :date_start and Time = :time_start");
            $stmt->bindValue(':date_start', $dateStart);
            $stmt->bindValue(':time_start', $timeStart);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        if ($this->dedicatedDb->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if the available package exists in database
     */
    public function packageAvailableExists(string $name) : bool
    {
        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM packages_available WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        if ($this->dedicatedDb->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if the available package and its version exists in database
     */
    public function packageVersionAvailableExists(string $name, string $version) : bool
    {
        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM packages_available WHERE Name = :name and Version = :version");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':version', $version);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        if ($this->dedicatedDb->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Count the number of packages installed, updated, removed... over the last X days.
     */
    public function countByStatusOverDays(string $status, string $dateStart, string $dateEnd) : array
    {
        $array = array();

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Date, COUNT(*) as date_count FROM packages
            WHERE State = :status and Date BETWEEN :dateStart and :dateEnd
            GROUP BY Date
            UNION
            SELECT Date, COUNT(*) as date_count FROM packages_history
            WHERE State = :status and Date BETWEEN :dateStart and :dateEnd
            GROUP BY Date");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':dateStart', $dateStart);
            $stmt->bindValue(':dateEnd', $dateEnd);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->dedicatedDb->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            if (!array_key_exists($row['Date'], $array)) {
                $array[$row['Date']] = $row['date_count'];
            } else {
                $array[$row['Date']] += $row['date_count'];
            }
        }

        return $array;
    }
}
