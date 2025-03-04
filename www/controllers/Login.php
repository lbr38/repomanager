<?php

namespace Controllers;

use Exception;

class Login
{
    private $model;
    private $username;
    private $password;
    private $apiKey;
    private $firstName;
    private $lastName;
    private $email;
    private $role;

    public function __construct()
    {
        $this->model = new \Models\Login();
    }

    private function setUsername(string $username)
    {
        $this->username = Common::validateData($username);
    }

    private function setPassword(string $password)
    {
        $this->password = Common::validateData($password);
    }

    private function setFirstName(string $firstName = null)
    {
        $this->firstName = Common::validateData($firstName);
    }

    private function setLastName(string $lastName = null)
    {
        $this->lastName = Common::validateData($lastName);
    }

    private function setEmail(string $email = null)
    {
        $this->email = Common::validateData($email);
    }

    private function setRole(string $role)
    {
        $this->role = Common::validateData($role);
    }

    public function getApiKey()
    {
        return $this->apiKey;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function getRole()
    {
        return $this->role;
    }

    /**
     *  Get all users email from database
     */
    public function getEmails()
    {
        return $this->model->getEmails();
    }

    /**
     *  Get specified username hashed password from db
     */
    private function getHashedPasswordFromDb(string $username)
    {
        return $this->model->getHashedPasswordFromDb($username);
    }

    /**
     *  Get Id by username
     */
    public function getIdByUsername(string $username)
    {
        return $this->model->getIdByUsername($username);
    }

    /**
     *  Get username by user Id
     */
    public function getUsernameById(string $id)
    {
        return $this->model->getUsernameById($id);
    }

    /**
     *  Get username informations
     */
    public function getAll(string $username)
    {
        $userInfo = $this->model->getAll($username);

        $this->apiKey = $userInfo['Api_key'];
        $this->firstName = $userInfo['First_name'];
        $this->lastName = $userInfo['Last_name'];
        $this->role = $userInfo['Role_name'];
        $this->email = $userInfo['Email'];
    }

    /**
     *  Get users list from database
     */
    public function getUsers()
    {
        return $this->model->getUsers();
    }

    /**
     *  Add a new user in database
     */
    public function addUser(string $username, string $role)
    {
        if (!IS_SUPERADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        $username = strtolower(\Controllers\Common::validateData($username));
        $role = strtolower(\Controllers\Common::validateData($role));

        /**
         *  Check that username does not contain invalid characters
         */
        if (Common::isAlphanumDash($username) === false) {
            throw new Exception('Username cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Check that user role is valid
         */
        if ($role != "usage" and $role != "administrator") {
            throw new Exception('Selected user role is invalid');
        }

        /**
         *  Check that username does not already exist
         */
        if ($this->userExists($username) === true) {
            throw new Exception('Username <b>' . $username . '</b> already exists');
        }

        /**
         *  Generating a new random password
         */
        $password = $this->generateRandomPassword();

        /**
         *  Hashing password with a salt automatically generated
         */
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        if ($hashedPassword === false) {
            throw new Exception("Error while hashing user password");
        }

        /**
         *  Converting role as Id
         */
        if ($role == "administrator") {
            $role = 2;
        }
        if ($role == "usage") {
            $role = 3;
        }

        /**
         *  Insert new user in database
         */
        $this->model->addUserLocal($username, $hashedPassword, $role);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Created user: <b>$username</b>", 'success');

        /**
         *  Return temporary generated password
         */
        return $password;
    }

    public function addUserSSO(string $username, ?string $firstName, ?string $lastName, ?string $email, string $role): void
    {
        $username = Common::validateData($username);
        $firstName = Common::validateData($firstName);
        $lastName = Common::validateData($lastName);
        $email = Common::validateData($email);

        if ($firstName == null) {
            $firstName = $username;
        }

        /**
         *  Check that email is a valid email address
         */
        if (Common::validateMail($email) === false) {
            $email = null;
        }

        /**
         *  Check that username does not contain invalid characters
         */
        if (Common::isAlphanumDash($username, ['@', '.']) === false) {
            throw new Exception('Username cannot contain special characters except hyphen, underscore, at symbol and dot');
        }

        /**
         *  Check that username does not already exist
         */
        if ($this->userExistsLocal($username) === true) {
            throw new Exception('Username <b>' . $username . '</b> already exists (local)');
        }

        /**
         *  Converting role as Id
         */
        if ($role == "super-administrator") {
            $role = 1;
        }
        if ($role == "administrator") {
            $role = 2;
        }
        if ($role == "usage") {
            $role = 3;
        }

        if ($this->model->userExists($username) === true) {
            $this->model->edit($username, $firstName, $lastName, $email);
            $this->model->updateRole($username, $role);
        } else {
            $this->model->addUserSSO($username, $firstName, $lastName, $email, $role);
        }
    }

    /**
     *  Check that specified username / password couple matches with database
     */
    public function checkUsernamePwd(string $username, string $password)
    {
        $username = Common::validateData($username);

        /**
         *  Check that user exists in database
         */
        if ($this->userExistsLocal($username) !== true) {
            throw new Exception('Invalid login and/or password');
        }

        /**
         *  Get user hashed password from database
         */
        $hashedPassword = $this->getHashedPasswordFromDb($username);

        /**
         *  If result is empty then it is anormal, die
         */
        if (empty($hashedPassword)) {
            die();
        }

        /**
         *  If specified password does not match database password, then it is invalid
         */
        if (!password_verify($password, $hashedPassword)) {
            $myhistory = new \Controllers\History();
            $myhistory->set($username, 'Authentication failed: Invalid password', 'error');
            throw new Exception('Invalid login and/or password');
        }
    }

    /**
     *  Edit user personnal informations
     */
    public function edit(string $username, string $firstName = null, string $lastName = null, string $email = null)
    {
        $username = Common::validateData($username);

        if (!empty($firstName)) {
            $firstName = Common::validateData($firstName);
        }
        if (!empty($lastName)) {
            $lastName = Common::validateData($lastName);
        }
        if (!empty($email)) {
            $email = Common::validateData($email);

            /**
             *  Check that email is a valid email address
             */
            if (Common::validateMail($email) === false) {
                throw new Exception('Invalid email address format');
            }
        }

        /**
         *  Check that user exists
         */
        if (!$this->userExistsLocal($username)) {
            throw new Exception("User <b>$username</b> does not exist (local)");
        }

        /**
         *  Update in database
         */
        $this->model->edit($username, $firstName, $lastName, $email);

        /**
         *  Update sessions variables with new values
         */
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name']  = $lastName;
        $_SESSION['email']      = $email;

        $myhistory = new \Controllers\History();
        $myhistory->set($username, "Personal informations modification", 'success');
    }

    /**
     *  Changing user password
     */
    public function changePassword(string $username, string $actualPassword, string $newPassword, string $newPasswordRetype)
    {
        $username = Common::validateData($username);

        /**
         *  Check that user exists
         */
        if ($this->userExistsLocal($username) !== true) {
            throw new Exception("User <b>$username</b> does not exist (local)");
        }

        /**
         *  Check that the actual password is valid
         */
        $this->checkUsernamePwd($username, $actualPassword);

        /**
         *  Now checking that actual password matches actual password in database
         */

        /**
         *  Get actual hashed password in database
         */
        $actualPasswordHashed = $this->getHashedPasswordFromDb($username);

        /**
         *  If result is empty then it is anormal, die
         */
        if (empty($actualPasswordHashed)) {
            die();
        }

        /**
         *  Check that new specified password and its retype are the same
         */
        if ($newPassword !== $newPasswordRetype) {
            throw new Exception('New password and password confirm are different');
        }

        /**
         *  Check that new specified password is different that the actual one in database
         */
        if (password_verify($newPassword, $actualPasswordHashed)) {
            throw new Exception('New password must be different then the actual one');
        }

        /**
         *  Hashing new password
         */
        $newPasswordHashed = password_hash($newPassword, PASSWORD_BCRYPT);

        /**
         *  Update in database
         */
        $this->model->updatePassword($username, $newPasswordHashed);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Password modification", 'success');
    }

    /**
     *  Reset specified user password
     */
    public function resetPassword(string $id)
    {
        if (!IS_SUPERADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        /**
         *  Get username
         */
        $username = $this->getUsernameById($id);

        if (empty($username)) {
            throw new Exception("Specified user does not exist");
        }

        /**
         *  Generating a new password
         */
        $password = $this->generateRandomPassword();

        /**
         *  Hashing password with salt
         */
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        if ($hashedPassword === false) {
            throw new Exception("Error while creating a new password for user <b>$username</b>");
        }

        /**
         *  Adding new hashed password in database
         */
        $this->model->updatePassword($username, $hashedPassword);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Reset password of user <b>$username</b>", 'success');

        /**
         *  Return new password
         */
        return $password;
    }

    /**
     *  Generate random password
     */
    private function generateRandomPassword()
    {
        $combinaison = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@$+-=%|{}[]&";
        $shuffle = str_shuffle($combinaison);

        return substr($shuffle, 0, 16);
    }

    /**
     *  Generate random clear API key
     */
    public function generateApiKey()
    {
        /**
         *  API key must be unique, loop until we get a unique one
         */
        while (empty($apiKey) or $this->apiKeyValid($apiKey) === true) {
            $combinaison = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
            $shuffle = str_shuffle($combinaison);
            $apiKey = 'ak_' . substr($shuffle, 0, 32);
        }

        return $apiKey;
    }

    /**
     *  Return true if API key is part of one of the hashed API key in database
     */
    public function apiKeyValid(string $apiKey)
    {
        /**
         *  Get all users to retrieve their API key
         */
        $usersList = $this->model->getUsers();

        /**
         *  Loop through all users API key
         */
        foreach ($usersList as $user) {
            /**
             *  Test if specified API key is one of the users API key
             */
            if (password_verify($apiKey, $user['Api_key'])) {
                return true;
            }
        }

        return false;
    }

    /**
     *  Return true if specified API key is an Admin API key
     */
    public function apiKeyIsAdmin(string $apiKey)
    {
        /**
         *  Get all users to retrieve their API key
         */
        $usersList = $this->model->getUsers();

        /**
         *  Loop through all users API key
         */
        foreach ($usersList as $user) {
            /**
             *  Test if specified API key is one of the users API key
             */
            if (password_verify($apiKey, $user['Api_key'])) {
                /**
                 *  Then check if user is an Admin
                 */
                if ($user['Role_name'] === 'super-administrator' or $user['Role_name'] === 'administrator') {
                    return true;
                }

                return false;
            }
        }

        return false;
    }

    /**
     *  Update user API key
     */
    public function updateApiKey(string $username, string $apiKey)
    {
        $username = Common::validateData($username);

        /**
         *  Check that user exists
         */
        if ($this->userExists($username) !== true) {
            throw new Exception("User $username does not exist");
        }

        /**
         *  Hashing API key with salt
         */
        $hashedApiKey = password_hash($apiKey, PASSWORD_BCRYPT);
        if ($hashedApiKey === false) {
            throw new Exception("Error while hashing API key");
        }

        /**
         *  Update API key in database
         */
        $this->model->updateApiKey($username, $hashedApiKey);
    }

    /**
     *  Delete specified user
     */
    public function deleteUser(string $id)
    {
        if (!IS_SUPERADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        /**
         *  Get username
         */
        $username = $this->getUsernameById($id);

        if (empty($username)) {
            throw new Exception("Specified user does not exist");
        }

        /**
         *  Disabling user in database
         *  The user is being kept in database for history reasons but its status is set on 'deleted' and the user become unusuable
         *  Its password is removed from database
         */
        $this->model->deleteUser($id);

        $myhistory = new \Controllers\History();
        $myhistory->set($_SESSION['username'], "Delete user <b>$username</b>", 'success');
    }

    /**
     *  Check if user exists in database and is local
     */
    public function userExistsLocal(string $username): bool
    {
        return $this->model->userExists($username, 'local');
    }

    /**
     *  Check if user exists in database
     */
    public function userExists(string $username)
    {
        return $this->model->userExists($username);
    }
}
