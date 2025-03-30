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
            $stmt = $this->db->prepare("DELETE FROM users WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
