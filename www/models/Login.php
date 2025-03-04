<?php

namespace Models;

use Exception;

class Login extends Model
{
    public function __construct()
    {
        $this->getConnection('main');
    }

    /**
     *  Get all users email from database
     */
    public function getEmails()
    {
        $emails = array();

        try {
            $result = $this->db->query("SELECT Email FROM users WHERE State = 'active'");
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $emails[] = $row['Email'];
        }

        return $emails;
    }

    /**
     *  Return specified username hashed password from db
     */
    public function getHashedPasswordFromDb(string $username)
    {
        $password = '';

        try {
            $stmt = $this->db->prepare("SELECT Password FROM users WHERE username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $password = $row['Password'];
        }

        return $password;
    }

    /**
     *  Get Id by username
     */
    public function getIdByUsername(string $username)
    {
        $id = '';

        try {
            $stmt = $this->db->prepare("SELECT Id FROM users WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $id = $row['Id'];
        }

        return $id;
    }

    /**
     *  Get username by user Id
     */
    public function getUsernameById(string $id)
    {
        $username = '';

        try {
            $stmt = $this->db->prepare("SELECT Username FROM users WHERE Id = :id and State = 'active'");
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
     *  Return username informations
     */
    public function getAll(string $username)
    {
        $userInfo = '';

        try {
            $stmt = $this->db->prepare("SELECT users.Username, users.Api_key, users.First_name, users.Last_name, users.Email, user_role.Name as Role_name FROM users JOIN user_role ON users.Role = user_role.Id WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $userInfo = $row;
        }

        return $userInfo;
    }

    /**
     *  Return users list from database
     */
    public function getUsers()
    {
        $users = array();

        try {
            $result = $this->db->query("SELECT users.Id, users.Username, users.Api_key, users.First_name, users.Last_name, users.Email, users.Type, user_role.Name as Role_name FROM users JOIN user_role ON users.Role = user_role.Id WHERE State = 'active' ORDER BY Username ASC");
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }

        return $users;
    }

    /**
     *  Add a new user in database
     */
    public function addUserLocal(string $username, string $hashedPassword, string $role): void
    {
        $this->addUser($username, $hashedPassword, $role, $username, null, null, 'local');
    }

    /**
     *  Add a new user in database
     */
    public function addUserSSO(string $username, string $firstName, string $lastName, $email, string $role): void
    {
        $this->addUser($username, null, $role, $firstName, $lastName, $email, 'sso');
    }

    /**
     *  Add a new user in database
     */
    public function addUser(string $username, string $hashedPassword = null, string $role, string $firstName, string $lastName = null, string $email = null, string $type): void
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
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update user personnal info in database
     */
    public function edit(string $username, string $firstName = null, string $lastName = null, string $email = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET First_name = :firstName, Last_name = :lastName, Email = :email WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':firstName', $firstName);
            $stmt->bindValue(':lastName', $lastName);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Set user status on 'deleted' in database
     */
    public function deleteUser(string $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET State = 'deleted', Api_key = null, Password = null WHERE Id = :id");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Check if user exists
     */
    public function userExists(string $username, string $type = null): bool
    {
        try {
            if (empty($type)) {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE Username = :username AND State = 'active'");
            } else {
                $stmt = $this->db->prepare("SELECT * FROM users WHERE Username = :username AND State = 'active' AND Type = :type");
                $stmt->bindValue(':type', $type);
            }
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;
    }

    /**
     *  Update user password in database
     */
    public function updatePassword(string $username, string $hashedPassword)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET Password = :password WHERE Username = :username and State = 'active' and Type = 'local'");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update user API key in database
     */
    public function updateApiKey(string $username, string $apiKey)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET Api_key = :apikey WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':apikey', $apiKey);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }

    /**
     *  Update user role in database
     */
    public function updateRole(string $username, string $role): void
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET Role = :role WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':role', $role);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->db->logError($e);
        }
    }
}
