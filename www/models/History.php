<?php

namespace Models;

use Exception;

class History extends Model
{
    public function __construct()
    {
        /**
         *  Open database
         */
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
     *  Add new history line in database
     */
    public function set(string $userId, string $username, string $action, string $ip, string $ipForwarded, string $userAgent, string $state) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO history ('Date', 'Time', 'Id_user', 'Username', 'Action', 'Ip', 'Ip_forwarded', 'User_agent', 'State') VALUES (:date, :time, :id, :username, :action, :ip, :ipForwarded, :userAgent, :state)");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':id', $userId);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':ip', $ip);
            $stmt->bindValue(':ipForwarded', $ipForwarded);
            $stmt->bindValue(':userAgent', $userAgent);
            $stmt->bindValue(':state', $state);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
