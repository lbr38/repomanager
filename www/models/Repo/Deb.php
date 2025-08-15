<?php

namespace Models\Repo;

use Exception;

class Deb extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return the Id of a repository by its name, distribution and component/section
     */
    public function getIdByNameDistComponent(string $name, string $distribution, string $component) : int|null
    {
        $id = null;

        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Dist = :distribution AND Section = :component");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':distribution', $distribution, SQLITE3_TEXT);
            $stmt->bindValue(':component', $component, SQLITE3_TEXT);
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
     *  Return true if a repository with the specified name, distribution and component/section exists
     */
    public function exists(string $name, string $distribution, string $component) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM repos WHERE Name = :name AND Dist = :distribution AND Section = :component");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':distribution', $distribution, SQLITE3_TEXT);
            $stmt->bindValue(':component', $component, SQLITE3_TEXT);
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
     *  Add a new DEB repository
     */
    public function add(string $name, string $distribution, string $component, string $source = '') : void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO repos ('Name', 'Dist', 'Section', 'Source', 'Package_type') VALUES (:name, :distribution, :component, :source, 'deb')");
            $stmt->bindValue(':name', $name, SQLITE3_TEXT);
            $stmt->bindValue(':distribution', $distribution, SQLITE3_TEXT);
            $stmt->bindValue(':component', $component, SQLITE3_TEXT);
            $stmt->bindValue(':source', $source, SQLITE3_TEXT);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
