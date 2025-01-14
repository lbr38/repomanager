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
     *  Retrieve the list of inventoried packages in database
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
     *  Retrieve the list of installed packages in database
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
     *  Retrieve the list of packages available for update in database
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
