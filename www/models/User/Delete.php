<?php

namespace Models\User;

use Exception;

class Delete extends \Models\User\User
{
    /**
     *  Delete user
     */
    public function delete(string $id) : void
    {
        try {
            // Delete user
            $stmt = $this->db->prepare("DELETE FROM users WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();

            // Delete user permissions
            $stmt = $this->db->prepare("DELETE FROM user_permissions WHERE User_id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
