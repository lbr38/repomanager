<?php

namespace Models;

use Exception;

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
    public function new(string $name)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO env (Name) VALUES (:name)");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Delete env from database
     */
    public function delete(string $name)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM env WHERE Name = :name");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
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
        $datas = array();

        $result = $this->db->query("SELECT Name FROM env");

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $datas[] = $row['Name'];
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
     *  Return true if env exists in database
     */
    public function exists(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM env WHERE Name = :env");
            $stmt->bindValue(':env', $name);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }
}
