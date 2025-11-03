<?php

namespace Models\Host\Package;

use \Controllers\Database\Log as DbLog;
use Exception;

class Event extends \Models\Model
{
    public function __construct(int $hostId)
    {
        // Open database
        $this->getConnection('host', $hostId);
    }

    /**
     *  Get event by its ID
     */
    public function get(int $id) : array
    {
        $data = [];

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT * FROM events WHERE Id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Get list of all events date
     */
    public function getDates(bool $withOffset, int $offset) : array
    {
        $data = [];

        try {
            $query = "SELECT DISTINCT Date FROM events ORDER BY Date DESC";

            // Add offset if needed
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            $stmt = $this->dedicatedDb->prepare($query);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row['Date'];
        }

        return $data;
    }

    /**
     *  Retrieve informations about all actions performed on host packages (install, update, remove...)
     *  It is possible to add an offset to the request
     */
    public function getHistory(bool $withOffset, int $offset) : array
    {
        $data = [];

        try {
            $query = "SELECT * FROM events ORDER BY Date DESC, Time DESC";

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
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            /**
             *  Add a column Event_type to the result to define that it is an 'event'. Will be useful when displaying data.
             */
            $row['Event_type'] = 'event';
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return true if event exists
     */
    public function exists(int $id) : bool
    {
        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Id FROM events WHERE Id = :id");
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->dedicatedDb->isempty($result) === true) {
            return false;
        }

        return true;
    }
}
