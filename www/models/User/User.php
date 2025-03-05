<?php

namespace Models\User;

use Exception;

class User extends \Models\Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Return user informations
     */
    public function get(int $id) : array
    {
        $data = [];

        try {
            $stmt = $this->db->prepare("SELECT users.Id as userId, users.Username, users.Api_key, users.First_name, users.Last_name, users.Email, user_role.Name as Role_name
            FROM users JOIN user_role ON users.Role = user_role.Id
            WHERE users.Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row;
        }

        return $data;
    }

    /**
     *  Return users list from database
     */
    public function getUsers() : array
    {
        $users = array();

        try {
            $result = $this->db->query("SELECT users.Id, users.Username, users.Api_key, users.First_name, users.Last_name, users.Email, users.Type, user_role.Name as Role_name FROM users JOIN user_role ON users.Role = user_role.Id ORDER BY Username ASC");
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }

        return $users;
    }

    /**
     *  Get all users email from database
     */
    public function getEmails() : array
    {
        $emails = array();

        try {
            $result = $this->db->query("SELECT Email FROM users");
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $emails[] = $row['Email'];
        }

        return $emails;
    }

    /**
     *  Get user Id by username and type (optional)
     */
    public function getIdByUsername(string $username, string|null $type) : int|null
    {
        $data = null;

        try {
            $query = "SELECT Id FROM users WHERE Username = :username";

            // If a type is specified, add it to the query
            if (!empty($type)) {
                $query .= " AND Type = :type";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':type', $type);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Id'];
        }

        return $data;
    }

    /**
     *  Get username by user Id
     */
    public function getUsernameById(string $id) : string
    {
        $username = '';

        try {
            $stmt = $this->db->prepare("SELECT Username FROM users WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $username = $row['Username'];
        }

        return $username;
    }

    /**
     *  Get role by user Id
     */
    public function getRoleById(string $id) : string
    {
        $data = '';

        try {
            $stmt = $this->db->prepare("SELECT Role FROM users WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Role'];
        }

        return $data;
    }

    /**
     *  Return specified user hashed password from db
     */
    public function getHashedPasswordFromDb(int $id) : string
    {
        $data = '';

        try {
            $stmt = $this->db->prepare("SELECT Password FROM users WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $data = $row['Password'];
        }

        return $data;
    }

    /**
     *  Return true if user exists in database
     */
    public function exists(string $username, string $type = null): bool
    {
        try {
            $query = "SELECT * FROM users WHERE Username = :username";

            if (!empty($type)) {
                $query .= " AND Type = :type";
            }

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':username', $username);

            if (!empty($type)) {
                $stmt->bindValue(':type', $type);
            }
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Return true if user Id exists in database
     */
    public function existsId(int $id) : bool
    {
        try {
            $stmt = $this->db->prepare("SELECT Id FROM users WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }

        if ($this->db->isempty($result)) {
            return false;
        }

        return true;
    }

    /**
     *  Update user API key in database
     */
    public function updateApiKey(string $username, string $apiKey) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET Api_key = :apikey WHERE Username = :username");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':apikey', $apiKey);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update user password in database
     */
    public function updatePassword(int $id, string $hashedPassword) : void
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET Password = :password WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->execute();
        } catch (Exception $e) {
            $this->db->logError($e->getMessage());
        }
    }
}
