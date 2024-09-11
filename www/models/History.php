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
     */
    public function getAll()
    {
        $datas = array();

        try {
            $result = $this->db->query("SELECT history.Id, history.Date, history.Time, history.Action, history.State, users.First_name, users.Last_name, users.Username FROM history JOIN users ON history.Id_user = users.Id ORDER BY Date DESC, Time DESC");
        } catch (\Exception $e) {
            $this->db->logError($e);
            return;
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Retrieve all history from a user
     */
    public function getByUser(int $userId)
    {
        $datas = array();

        try {
            $stmt = $this->db->prepare("SELECT history.Id, history.Date, history.Time, history.Action, history.State, users.First_name, users.Last_name, users.Username FROM history JOIN users ON history.Id_user = users.Id WHERE history.Id_user = :userid ORDER BY Date DESC, Time DESC");
            $stmt->bindValue(':userid', $userId);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
            return;
        }

        $datas = array();

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  Add new history line in database
     */
    public function set(string $userId, string $action, string $state)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO history ('Date', 'Time', 'Id_user', 'Action', 'State') VALUES (:date, :time, :id, :action, :state)");
            $stmt->bindValue(':date', date('Y-m-d'));
            $stmt->bindValue(':time', date('H:i:s'));
            $stmt->bindValue(':id', $userId);
            $stmt->bindValue(':action', $action);
            $stmt->bindValue(':state', $state);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }
}
