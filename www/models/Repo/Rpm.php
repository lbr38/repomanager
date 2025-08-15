<?php

namespace Models\Repo;

use Exception;

class Rpm extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the Id of a repository by its name and release version
     */
    public function getIdByNameReleasever(string $name, int $releaseVersion) : int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Releasever = :releaseVersion");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':releaseVersion', $releaseVersion, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Return true if a repository with the specified name and release version exists
     */
    public function exists(string $name, int $releaseVersion) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Releasever = :releaseVersion");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':releaseVersion', $releaseVersion, SQLITE3_INTEGER);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Add a new RPM repository
     */
    public function add(string $name, int $releaseVersion, string $source = '') : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos ('Name', 'Releasever', 'Source', 'Package_type') VALUES (:name, :releaseVersion, :source, 'rpm')");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':releaseVersion', $releaseVersion, SQLITE3_TEXT);
            $stmt->bindValue(':source', $source, SQLITE3_TEXT);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
