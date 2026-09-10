<?php

namespace Models\Layout;

use Controllers\Database\Log as DbLog;
use Exception;

class ContainerReload extends \Models\Model
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
        $containers = [];

        try {
            $result = $this->db->query("SELECT * FROM layout_container_state");
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $containers[] = $row;
        }

        return $containers;
    }

    /**
     *  Add a new layout container state
     */
    public function add(string $name)
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO layout_container_state (Container) VALUES (:name)");
            $stmt->bindValue(':name', $name);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
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
        } catch (Exception $e) {
            DbLog::error($e);
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Delete the specified layout container states
     */
    public function delete(array $names): void
    {
        if (empty($names)) {
            return;
        }

        try {
            $placeholders = implode(',', array_fill(0, count($names), '?'));

            $stmt = $this->db->prepare("DELETE FROM layout_container_state WHERE Container IN (" . $placeholders . ")");

            foreach (array_values($names) as $index => $name) {
                $stmt->bindValue($index + 1, $name);
            }

            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Clean all containers entries
     */
    public function clean()
    {
        try {
            $this->db->exec("DELETE FROM layout_container_state");
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
