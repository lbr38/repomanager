<?php

namespace Models\Host\Package;

use Exception;

class Event extends \Models\Model
{
    public function __construct(int $hostId)
    {
        // Open database
        $this->getConnection('host', $hostId);
    }

    /**
     *  Retrieves the details of an event for a specific type of packages (installed, updated, etc...)
     */
    public function getDetails(string $eventId, string $packageState) : array
    {
        $data = array();

        try {
            $stmt = $this->dedicatedDb->prepare("SELECT Name, Version FROM packages
            WHERE Id_event = :id_event and State = :state
            UNION
            SELECT Name, Version FROM packages_history
            WHERE Id_event = :id_event and State = :state");
            $stmt->bindValue(':id_event', \Controllers\Common::validateData($eventId));
            $stmt->bindValue(':state', \Controllers\Common::validateData($packageState));
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
     *  Retrieve informations about all actions performed on host packages (install, update, remove...)
     *  It is possible to add an offset to the request
     */
    public function getHistory(bool $withOffset, int $offset) : array
    {
        $data = array();

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
            $this->dedicatedDb->logError($e);
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
}
