<?php
namespace Controllers\User;

use Exception;
use \Controllers\Common;

class Create extends User
{
    private $defaultPermissions = [
        'repositories' => [
            'allowed-actions' => [
                'repos' => [],
                'hosts' => [],
            ],
            'view' => [
                'all',
                'groups' => []
            ],
        ]
    ];

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
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        $username = strtolower(Common::validateData($username));
        $role = strtolower(Common::validateData($role));

        /**
         *  Check that username does not contain invalid characters
         */
        if (Common::isAlphanumDash($username) === false) {
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
        if ($this->exists($username) === true) {
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
        if ($role == 'administrator') {
            $role = 2;
        }
        if ($role == 'usage') {
            $role = 3;
        }

        /**
         *  Insert new user in database
         */
        $this->model->create($username, $hashedPassword, $role, null, null, null, 'local', $this->defaultPermissions);

        /**
         *  Add history
         */
        $this->historyController->set("Created user: $username", 'success');

        /**
         *  Return temporary generated password
         */
        return $password;
    }

    /**
     *  Add a new SSO user in database
     */
    public function createSSO(string $username, ?string $firstName, ?string $lastName, ?string $email, string $role): void
    {
        $userEditController = new \Controllers\User\Edit();
        $username  = Common::validateData($username);
        $firstName = Common::validateData($firstName);
        $lastName  = Common::validateData($lastName);
        $email     = Common::validateData($email);

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
            throw new Exception('Username cannot contain special characters except hyphen, underscore, @ symbol and dot');
        }

        /**
         *  Check that username does not already exist locally
         *  You cannot create a SSO user with the same username as a local user
         */
        if ($this->exists($username, 'local') === true) {
            throw new Exception('Username ' . $username . ' already exists');
        }

        /**
         *  Check that user role is valid
         */
        if (!in_array($role, $this->validRoles)) {
            throw new Exception('User role is invalid');
        }

        /**
         *  Converting role as Id
         */
        // if ($role == 'super-administrator') {
        //     $role = 1;
        // }
        if ($role == 'administrator') {
            $role = 2;
        }
        if ($role == 'usage') {
            $role = 3;
        }

        /**
         *  Insert new user in database if it is its first connection
         *  If the user already exists (from a previous SSO connection) then update its informations (as it may have changed)
         */
        if (!$this->exists($username, 'sso')) {
            $this->model->create($username, null, $role, $firstName, $lastName, $email, 'sso', $this->defaultPermissions);
        } else {
            /**
             *  Get user Id
             */
            $id = $this->getIdByUsername($username, 'sso');

            /**
             *  If the Id could not be found then throw an error
             */
            if (empty($id)) {
                throw new Exception('Could not find associated user Id of username ' . $username);
            }

            /**
             *  Edit user informations
             */
            $userEditController->edit($id, 'sso', $firstName, $lastName, $email);

            /**
             *  Edit user role
             */
            $userEditController->updateRole($id, $role);
        }
    }
}
