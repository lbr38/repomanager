<?php

namespace Models\User;

use Exception;
use \Controllers\Database\Log as DbLog;

class Create extends \Models\User\User
{
    /**
     *  Add a new user in database
     */
    public function create(string $username, string $hashedPassword = null, string $role, string $firstName = null, string $lastName = null, string $email = null, string $type, array $permissions): void
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

            // Convert permissions array to JSON
            try {
                $permissions = json_encode($permissions, JSON_THROW_ON_ERROR);
            } catch (Exception $e) {
                throw new Exception('Error encoding permissions to JSON: ' . $e->getMessage());
            }

            // Create empty set of permissions for new user
            $stmt = $this->db->prepare("INSERT INTO user_permissions (Permissions, User_id) VALUES (:permissions, :id)");
            $stmt->bindValue(':permissions', $permissions);
            $stmt->bindValue(':id', $this->db->lastInsertRowID());
            $stmt->execute();
        } catch (Exception $e) {
            DbLog::error($e);
        }
    }
}
