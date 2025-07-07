<?php

namespace Models\User;

use Exception;

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
            $this->db->logError($e->getMessage());
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
        try {
            $stmt = $this->db->prepare("UPDATE user_permissions SET Permissions = :permissions WHERE User_id = :id");
            $stmt->bindValue(':permissions', $permissions);
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
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
            $this->db->logError($e->getMessage());
        }
    }
}
