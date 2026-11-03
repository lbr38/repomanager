<?php

namespace Controllers\User;

require_once ROOT . '/libs/vendor/autoload.php';

use Exception;
use Controllers\Utils\Validate;
use Controllers\Utils\Random;

class User
{
    protected $model;
    protected $historyController;
    protected $validTypes = ['local', 'sso'];
    protected $validRoles = ['administrator', 'usage'];
    protected $rolesId = ['administrator' => 2, 'usage' => 3];

    /**
     *  API keys are stored as a SHA-256 hash with this prefix
     *  API keys are long random strings, a fast hash is enough and allows to check a key without any CPU cost
     *  Older API keys are stored as a bcrypt hash, they are converted the first time they are used
     */
    private const API_KEY_HASH_PREFIX = 'sha256:';

    /**
     *  Users matching the API keys already checked during the current request
     */
    private static array $apiKeyUsers = [];

    public function __construct()
    {
        $this->model = new \Models\User\User();
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
    public function getEmails() : array
    {
        return array_unique($this->model->getEmails());
    }

    /**
     *  Get user Id by username and type (optional)
     */
    public function getIdByUsername(string $username, string|null $type) : int|null
    {
        return $this->model->getIdByUsername($username, $type);
    }

    /**
     *  Get user Id by API key
     */
    public function getIdByApiKey(string $apiKey) : int|null
    {
        $user = $this->getUserByApiKey($apiKey);

        if ($user === null) {
            return null;
        }

        return $user['Id'];
    }

    /**
     *  Return the hash of an API key, as stored in database
     */
    private function hashApiKey(string $apiKey) : string
    {
        return self::API_KEY_HASH_PREFIX . hash('sha256', $apiKey);
    }

    /**
     *  Return the user matching the specified API key, or null if no user matches
     */
    private function getUserByApiKey(string $apiKey) : array|null
    {
        if (empty($apiKey)) {
            return null;
        }

        $hashedApiKey = $this->hashApiKey($apiKey);

        // Return the result if this API key has already been checked during the current request
        if (array_key_exists($hashedApiKey, self::$apiKeyUsers)) {
            return self::$apiKeyUsers[$hashedApiKey];
        }

        $users = $this->getUsers();
        $matchingUser = null;

        // Compare the hash of the API key with the hash of every user API key
        foreach ($users as $user) {
            if (!empty($user['Api_key']) and hash_equals($user['Api_key'], $hashedApiKey)) {
                $matchingUser = $user;
                break;
            }
        }

        // Otherwise check older API keys (bcrypt hash), then convert the matching one to the new format
        if ($matchingUser === null) {
            foreach ($users as $user) {
                if (empty($user['Api_key']) or str_starts_with($user['Api_key'], self::API_KEY_HASH_PREFIX)) {
                    continue;
                }

                if (password_verify($apiKey, $user['Api_key'])) {
                    $matchingUser = $user;
                    $this->model->updateApiKeyById($user['Id'], $hashedApiKey);
                    break;
                }
            }
        }

        self::$apiKeyUsers[$hashedApiKey] = $matchingUser;

        return $matchingUser;
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
            throw new Exception('An error occurred while checking user password');
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
    public function exists(string $username, string $type = '') : bool
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
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789@$+-=%|{}[]&';

        return Random::strongString(16, $characters);
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
            $apiKey = 'ak_' . bin2hex(random_bytes(24));
        }

        return $apiKey;
    }

    /**
     *  Return true if API key is part of one of the hashed API key in database
     */
    public function apiKeyValid(string $apiKey)
    {
        return $this->getUserByApiKey($apiKey) !== null;
    }

    /**
     *  Return true if specified API key is an Admin API key
     */
    public function apiKeyIsAdmin(string $apiKey)
    {
        $user = $this->getUserByApiKey($apiKey);

        if ($user === null) {
            return false;
        }

        return $user['Role_name'] === 'super-administrator' or $user['Role_name'] === 'administrator';
    }

    /**
     *  Update user API key
     */
    public function updateApiKey(string $username, string $type, string $apiKey)
    {
        $username = Validate::string($username);

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
         *  Hashing API key
         */
        $hashedApiKey = $this->hashApiKey($apiKey);

        /**
         *  Update API key in database
         */
        $this->model->updateApiKey($username, $hashedApiKey);

        // Forget the API keys checked during the current request, as they may no longer be valid (or became valid)
        self::$apiKeyUsers = [];
    }
}
