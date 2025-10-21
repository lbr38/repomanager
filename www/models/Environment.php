<?php

namespace Models;

use Exception;
use \Controllers\Database\Log as DbLog;

class Environment extends Model
{
    public function __construct()
    {
        /**
         *  Open database
         */
        $this->getConnection('main');
    }

    /**
     *  Add new environment in database
     */
    public function add(string $name, string $color) : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO env (Name, Color) VALUES (:name, :color)");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':color', $color);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete env from database
     */
    public function delete(int $id) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM env WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete all env from database
     */
    public function deleteAll()
    {
        $this->db->exec("DELETE FROM env");
    }

    /**
     *  List all environments
     */
    public function listAll()
    {
        $datas = [];

        $result = $this->db->query("SELECT * FROM env");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row;
        }

        return $datas;
    }

    /**
     *  List default environment
     */
    public function default()
    {
        $default = '';

        $result = $this->db->query("SELECT Name FROM env LIMIT 1");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $default = $row['Name'];
        }

        return $default;
    }

    /**
     *  List last environment name
     */
    public function last()
    {
        $last = '';

        $result = $this->db->query("SELECT Id, Name FROM env ORDER BY Id DESC LIMIT 1");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $last = $row['Name'];
        }

        return $last;
    }

    /**
     *  Return true if env Id exists in database
     */
    public function existsId(int $id) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM env WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if env exists in database
     */
    public function exists(string $name) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM env WHERE Name = :env");
            $stmt->bindValue(':env', $name);
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
