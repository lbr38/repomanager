<?php

namespace Models\User;

use Exception;

class Edit extends \Models\User\User
{
    /**
     *  Update user personnal info in database
     */
    public function edit(int $id, string $firstName = '', string $lastName = '', string $email = '') : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET First_name = :firstName, Last_name = :lastName, Email = :email WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':firstName', $firstName);
            $stmt->bindValue(':lastName', $lastName);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update user role
     */
    public function updateRole(int $id, int $role) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET Role = :role WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':role', $role);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
