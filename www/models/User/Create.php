<?php

namespace Models\User;

use Exception;

class Create extends \Models\User\User
{
    /**
     *  Add a new user in database
     */
    public function create(string $username, string $hashedPassword = null, string $role, string $firstName = null, string $lastName = null, string $email = null, string $type): void
    {
        try {
            $stmt = $this->db->prepare("INSERT INTO users ('Username', 'Password', 'First_name', 'Last_name', 'Email', 'Role', 'State', 'Type') VALUES (:username, :password, :firstName, :lastName, :email, :role, 'active', :type)");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':firstName', $firstName);
            $stmt->bindValue(':lastName', $lastName);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':role', $role);
            $stmt->bindValue(':type', $type);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }
}
