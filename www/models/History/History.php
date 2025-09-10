<?php

namespace Models\History;

use Exception;

class History extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Retrieve all history
     *  It is possible to add an offset to the request
     */
    public function getAll(bool $withOffset = false, int $offset = 0) : array
    {
        $data = array();

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
            $this->db->logError($e);
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
        $data = array();

        try {
            $query = "SELECT history.Id, history.Date, history.Time, history.Action, history.State, users.First_name, users.Last_name, users.Username
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
            $this->db->logError($e);
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
            $this->db->logError($e);
        }
    }
}
