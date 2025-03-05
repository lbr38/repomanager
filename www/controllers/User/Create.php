<?php
namespace Controllers\User;

use Exception;

class Create extends User
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new \Models\User\Create();
    }

    /**
     *  Add a new user in database
     */
    public function create(string $username, string $role) : string
    {
        if (!IS_SUPERADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        $username = strtolower(\Controllers\Common::validateData($username));
        $role = strtolower(\Controllers\Common::validateData($role));

        /**
         *  Check that username does not contain invalid characters
         */
        if (\Controllers\Common::isAlphanumDash($username) === false) {
            throw new Exception('Username cannot contain special characters except hyphen and underscore');
        }

        /**
         *  Check that user role is valid
         */
        if (!in_array($role, $this->validRoles)) {
            throw new Exception('Selected user role is invalid');
        }

        /**
         *  Check that username does not already exist
         */
        if ($this->exists($username, 'local') === true) {
            throw new Exception('Username ' . $username . ' already exists');
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
            throw new Exception('Error while creating user password');
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
        $this->model->create($username, $hashedPassword, $role, null, null, null, 'local');

        // $myhistory = new \Controllers\History();
        // $myhistory->set($_SESSION['username'], "Created user: $username", 'success');

        /**
         *  Return temporary generated password
         */
        return $password;
    }
}
