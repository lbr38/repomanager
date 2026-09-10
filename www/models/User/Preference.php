<?php

namespace Models\User;

use Exception;
use Controllers\Database\Log as DbLog;

class Preference extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get user preferences
     */
    public function get(int $id): string
    {
        $data = '';

        try {
            $stmt = $this->db->prepare("SELECT Preferences FROM user_preferences WHERE User_id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Preferences'];
        }

        return $data;
    }

    /**
     *  Set user preferences
     */
    public function set(int $id, string $preferences): void
    {
        // First, delete existing preferences if any
        $this->delete($id);

        try {
            // Then insert new preferences
            $stmt = $this->db->prepare("INSERT INTO user_preferences (User_id, Preferences) VALUES (:id, :preferences)");
            $stmt->bindValue(':preferences', $preferences);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete user preferences
     */
    public function delete(int $id): void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM user_preferences WHERE User_id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
