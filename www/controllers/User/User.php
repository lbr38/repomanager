<?php

namespace Controllers\User;

require_once ROOT . '/libs/vendor/autoload.php';

use Exception;

class User
{
    protected $model;
    protected $historyController;
    protected $validTypes = ['local', 'sso'];
    protected $validRoles = ['administrator', 'usage'];
    protected $rolesId = ['administrator' => 2, 'usage' => 3];

    public function __construct()
    {
        $this->model = new \Models\User\User();
        $this->historyController = new \Controllers\History();
    }

    /**
     *  Get user informations
     */
    public function get(int $id) : array
    {
        return $this->model->get($id);
    }

    /**
     *  Get users list from database
     */
    public function getUsers() : array
    {
        return $this->model->getUsers();
    }

    /**
     *  Get all users email from database
     */
    public function getEmails()
    {
        return $this->model->getEmails();
    }

    /**
     *  Get user Id by username and type (optional)
     */
    public function getIdByUsername(string $username, string|null $type) : int|null
    {
        return $this->model->getIdByUsername($username, $type);
    }

    /**
     *  Get username by user Id
     */
    public function getUsernameById(string $id)
    {
        return $this->model->getUsernameById($id);
    }

    /**
     *  Get role by user Id
     */
    public function getRoleById(string $id) : string
    {
        return $this->model->getRoleById($id);
    }

    /**
     *  Get specified user hashed password
     */
    protected function getHashedPasswordFromDb(int $id) : string
    {
        return $this->model->getHashedPasswordFromDb($id);
    }

    /**
     *  Check that specified user / password couple matches with database
     */
    protected function checkUsernamePwd(int $id, string $password) : void
    {
        /**
         *  Check that the user exists
         */
        if ($this->existsId($id) === false) {
            throw new Exception('Invalid login');
        }

        /**
         *  Get user hashed password from database
         */
        $hashedPassword = $this->getHashedPasswordFromDb($id);

        /**
         *  If result is empty then it is anormal
         */
        if (empty($hashedPassword)) {
            throw new Exception('An error occured while checking user password');
        }

        /**
         *  If specified password does not match database passord, then it is invalid
         */
        if (!password_verify($password, $hashedPassword)) {
            throw new Exception('Bad password');
        }
    }

    /**
     *  Update user password in database
     */
    protected function updatePassword(int $id, string $hashedPassword) : void
    {
        $this->model->updatePassword($id, $hashedPassword);
    }

    /**
     *  Return true if user exists
     */
    public function exists(string $username, string $type = null) : bool
    {
        return $this->model->exists($username, $type);
    }

    /**
     *  Return true if user Id exists
     */
    public function existsId(int $id) : bool
    {
        return $this->model->existsId($id);
    }

    /**
     *  Generate random password
     */
    protected function generateRandomPassword()
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
        $usersList = $this->getUsers();

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
        $users = $this->getUsers();

        /**
         *  Loop through all users API key
         */
        foreach ($users as $user) {
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
    public function updateApiKey(string $username, string $type, string $apiKey)
    {
        $username = \Controllers\Common::validateData($username);

        if (!in_array($type, $this->validTypes)) {
            throw new Exception('Invalid user type');
        }

        /**
         *  Check that user exists
         */
        if ($this->exists($username, $type) !== true) {
            throw new Exception('User ' . $username . ' does not exist');
        }

        /**
         *  Hashing API key with salt
         */
        $hashedApiKey = password_hash($apiKey, PASSWORD_BCRYPT);
        if ($hashedApiKey === false) {
            throw new Exception('Error while hashing API key');
        }

        /**
         *  Update API key in database
         */
        $this->model->updateApiKey($username, $hashedApiKey);
    }
}
