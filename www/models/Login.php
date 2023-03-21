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
            \Controllers\Common::dbError($e);
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
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $password = $row['Password'];
        }

        return $password;
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
            \Controllers\Common::dbError($e);
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
            $stmt = $this->db->prepare("SELECT users.Username, users.First_name, users.Last_name, users.Email, user_role.Name as Role_name FROM users JOIN user_role ON users.Role = user_role.Id WHERE Username = :username and State = 'active'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
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
            $result = $this->db->query("SELECT users.Id, users.Username, users.First_name, users.Last_name, users.Email, users.Type, user_role.Name as Role_name FROM users JOIN user_role ON users.Role = user_role.Id WHERE State = 'active' ORDER BY Username ASC");
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }

        return $users;
    }

    /**
     *  Add a new user in database
     */
    public function addUser(string $username, string $hashedPassword, string $role)
    {
        /**
         *  Insertion de l'username, du mdp hashé et son salt en base de données
         */
        try {
            $stmt = $this->db->prepare("INSERT INTO users ('Username', 'Password', 'First_name', 'Role', 'State', 'Type') VALUES (:username, :password, :first_name, :role, 'active', 'local')");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':password', $hashedPassword);
            $stmt->bindValue(':first_name', $username);
            $stmt->bindValue(':role', $role);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Update user personnal info in database
     */
    public function edit(string $username, string $firstName = null, string $lastName = null, string $email = null)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET First_name = :firstName, Last_name = :lastName, Email = :email WHERE Username = :username and State = 'active' AND Type = 'local'");
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':firstName', $firstName);
            $stmt->bindValue(':lastName', $lastName);
            $stmt->bindValue(':email', $email);
            $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Set user status on 'deleted' in database
     */
    public function deleteUser(string $id)
    {
        try {
            $stmt = $this->db->prepare("UPDATE users SET State = 'deleted', Password = null WHERE Id = :id and Type = 'local'");
            $stmt->bindValue(':id', $id);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }
    }

    /**
     *  Check if user exists by returning its informations
     */
    public function userExists(string $username)
    {
        $user = '';

        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE Username = :username AND State = 'active' AND Type = 'local'");
            $stmt->bindValue(':username', $username);
            $result = $stmt->execute();
        } catch (\Exception $e) {
            \Controllers\Common::dbError($e);
        }

        if ($this->db->isempty($result) === true) {
            return false;
        }

        return true;

        // while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        //     $user = $row;
        // }

        // return $user;
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
            \Controllers\Common::dbError($e);
        }
    }
}
