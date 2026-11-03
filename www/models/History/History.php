<?php

namespace Models\History;

use Controllers\Database\Log as DbLog;
use Exception;

class History extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the number of failed local authentications for the specified username and IP address since the specified date and time
     */
    public function countFailedLogins(string $username, string $ip, string $date, string $time) : int
    {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) AS Count FROM history
            WHERE (Date > :date OR (Date = :date AND Time >= :time))
            AND State = 'error'
            AND Username = :username
            AND Ip = :ip
            AND Action LIKE 'Authentication failed for %(local account)%'");
            $stmt->bindValue(':date', $date);
            $stmt->bindValue(':time', $time);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':ip', $ip);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
            return 0;
        }

        $row = $result->fetchArray(SQLITE3_ASSOC);

        return (int) ($row['Count'] ?? 0);
    }

    /**
     *  Retrieve all history
     *  It is possible to add an offset to the request
     */
    public function getAll(bool $withOffset = false, int $offset = 0) : array
    {
        $data = [];

        try {
            $query = "SELECT * FROM history ORDER BY Date DESC, Time DESC";

            /**
             *  Add offset if needed
             */
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            $stmt = $this->db->prepare($query);
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
     *  Retrieve all history from a user
     *  It is possible to add an offset to the request
     */
    public function getByUserId(int $id, bool $withOffset = false, int $offset = 0) : array
    {
        $data = [];

        try {
            $query = "SELECT history.*
            FROM history JOIN users ON history.Id_user = users.Id
            WHERE history.Id_user = :id
            ORDER BY Date DESC, Time DESC";

            /**
             *  Add offset if needed
             */
            if ($withOffset === true) {
                $query .= " LIMIT 10 OFFSET :offset";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':id', $id);
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
     *  Clean history logs older than the specified date
     */
    public function cleanup(string $date) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM history WHERE Date < :date");
            $stmt->bindValue(':date', $date);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
