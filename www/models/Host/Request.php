<?php

namespace Models\Host;

use \Controllers\Database\Log as DbLog;
use Exception;

class Request extends \Models\Model
{
    public function __construct()
    {
        /**
         *  Open database
         */
        $this->getConnection('hosts');
    }

    /**
     *  Add a new request in database
     */
    public function new(int $hostId, string $request) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO requests ('Date', 'Time', 'Request', 'Status', 'Retry', 'Id_host') VALUES (:date, :time, :request, 'new', '0', :hostId)");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':request', $request);
            $stmt->bindValue(':hostId', $hostId);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Return all requests from database
     */
    public function get(string|null $status) : array
    {
        $requests = [];

        try {
            /**
             *  If a status is specified, we only get requests with this status
             */
            if (!empty($status)) {
                $stmt = $this->db->prepare("SELECT * FROM requests WHERE Status = :status ORDER BY Date DESC, Time DESC");
                $stmt->bindValue(':status', $status);
            } else {
                $stmt = $this->db->prepare("SELECT * FROM requests ORDER BY Date DESC, Time DESC");
            }
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $requests[] = $row;
        }

        return $requests;
    }

    /**
     *  Return the list of requests sent to the host
     *  It is possible to add an offset to the request
     */
    public function getByHostId(int $id, bool $withOffset, int $offset)
    {
        $data = [];

        try {
            $query = "SELECT * FROM requests WHERE Id_host = :id ORDER BY Date DESC, Time DESC";

            /**
             *  Add offset if needed
             */
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            /**
             *  Prepare query
             */
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $id, SQLITE3_INTEGER);
            $stmt->bindValue(':offset', $offset, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data[] = $row;
        }

        return $data;
    }

    /**
     *  Return the last pending request sent to the host
     */
    public function getLastPendingRequest(int $id)
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT * from requests WHERE Id_host = :id ORDER BY DATE DESC, TIME DESC LIMIT 1");
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
     *  Update request in database
     */
    public function update(int $id, string $status, string $info, string $responseJson) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE requests SET Status = :status, Info = :info, Response_json = :responseJson WHERE Id = :id");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':info', $info);
            $stmt->bindValue(':responseJson', $responseJson);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update request status in database
     */
    public function updateStatus(int $id, string $status) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE requests SET Status = :status WHERE Id = :id");
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update request info message in database
     */
    public function updateInfo(int $id, string $info) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE requests SET Info = :info WHERE Id = :id");
            $stmt->bindValue(':info', $info);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update request retry in database
     */
    public function updateRetry(int $id, int $retry) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE requests SET Retry = :retry WHERE Id = :id");
            $stmt->bindValue(':retry', $retry);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Update request next retry time in database
     */
    public function updateNextRetry(int $id, string $nextRetry) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE requests SET Next_retry = :nextRetry WHERE Id = :id");
            $stmt->bindValue(':nextRetry', $nextRetry);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Cancel request in database
     */
    public function cancel(int $id) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE requests SET Status = 'canceled', Info = Info || ' (canceled)' WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete request from database
     */
    public function delete(int $id) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM requests WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Get request package log details
     */
    public function getRequestPackageLog(int $id, string $package, string $status) : string|null
    {
        $data = '';

        // Example of Response_json content:
        // "update":{
        //     "status":"done",
        //     "success":{
        //         "count":7,
        //         "packages":{
        //             "brave-browser":{
        //                 "version":"xxxx",
        //                 "log":"xxxx"
        //             },
        //             "firefox":{
        //                 "version":"xxxx",
        //                 "log":"xxxx"
        //             },
        //         }
        //     }
        // }

        try {
            /**
             *  Extract the log of the specified package in the specified status
             */
            $stmt = $this->db->prepare("SELECT json_extract(Response_json, :path) as log FROM requests WHERE Id = :id");
            // Add quotes around the package name to avoid issues with package names containing dots (e.g. php8.1)
            $stmt->bindValue(':path', '$.update.' . $status . '.packages."' . $package . '".log');
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['log'];
        }

        return $data;
    }

    /**
     *  Return true if a package update request is running on the specified host
     */
    public function isPackageUpdateRequestRunning(int $hostId) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM requests
            WHERE Id_host = :hostId
            AND (Status = 'running' OR Status = 'new' OR Status = 'sent')
            AND (json_extract(COALESCE(Request, '{}'), '$.request') == 'request-packages-update' OR json_extract(COALESCE(Request, '{}'), '$.request') == 'request-all-packages-update')");
            $stmt->bindValue(':hostId', $hostId);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }
}
