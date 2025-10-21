<?php

namespace Models\User;

use Exception;
use \Controllers\Database\Log as DbLog;

class Permission extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get user permissions
     */
    public function get(int $id) : string
    {
        $data = '';

        try {
            $stmt = $this->db->prepare("SELECT Permissions FROM user_permissions WHERE User_id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Permissions'];
        }

        return $data;
    }

    /**
     *  Set user permissions
     */
    public function set(int $id, string $permissions) : void
    {
        // First, delete existing permissions if any
        $this->delete($id);

        try {
            // Then insert new permissions
            $stmt = $this->db->prepare("INSERT INTO user_permissions (User_id, Permissions) VALUES (:id, :permissions)");
            $stmt->bindValue(':permissions', $permissions);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }

    /**
     *  Delete user permissions
     */
    public function delete(int $id) : void
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM user_permissions WHERE User_id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
