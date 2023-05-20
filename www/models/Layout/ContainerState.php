<?php

namespace Models\Layout;

use Exception;

class ContainerState extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get all layout containers state
     */
    public function get()
    {
        $containers = array();

        try {
            $result = $this->db->query("SELECT * FROM layout_container_state");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $containers[] = $row;
        }

        return $containers;
    }

    /**
     *  Add a new layout container state
     */
    public function add(string $name, string $id)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO layout_container_state (Container, Id) VALUES (:name, :id)");
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update a layout container state
     */
    public function update(string $name, $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE layout_container_state SET Id = :id WHERE Container = :name");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Check if a container name exists
     */
    public function exists(string $name)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM layout_container_state WHERE Container = :name");
            $stmt->bindValue(':name', $name);
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
