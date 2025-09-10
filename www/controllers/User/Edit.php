<?php
namespace Controllers\User;

use Exception;
use \Controllers\History\Save as History;

class Edit extends User
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new \Models\User\Edit();
    }

    /**
     *  Edit user personal informations
     */
    public function edit(int $id, string $type, string $firstName = '', string $lastName = '', string $email = '') : void
    {
        $firstName = \Controllers\Common::validateData($firstName);
        $lastName  = \Controllers\Common::validateData($lastName);
        $email     = \Controllers\Common::validateData($email);

        /**
         *  Check that user type is valid
         */
        if (!in_array($type, $this->validTypes)) {
            throw new Exception('Invalid user type');
        }

        /**
         *  Check that email is a valid email address
         */
        if (!empty($email)) {
            if (\Controllers\Common::validateMail($email) === false) {
                throw new Exception('Invalid email address format');
            }
        }

        /**
         *  Check that user exists
         */
        if (!$this->existsId($id)) {
            throw new Exception('User does not exist');
        }

        /**
         *  Update informations in database
         */
        $this->model->edit($id, $firstName, $lastName, $email);

        /**
         *  Update sessions variables with new values
         */
        $_SESSION['first_name'] = $firstName;
        $_SESSION['last_name']  = $lastName;
        $_SESSION['email']      = $email;

        History::set('Personal informations modification');
    }

    /**
     *  Change user password
     */
    public function changePassword(int $id, string $type, string $actualPassword, string $newPassword, string $newPasswordRetype) : void
    {
        /**
         *  Check that user type is valid
         */
        if (!in_array($type, $this->validTypes)) {
            throw new Exception('Invalid user type');
        }

        /**
         *  SSO users cannot change their password
         */
        if ($type != 'local') {
            throw new Exception('You are not allowed to change your password (not a local account)');
        }

        /**
         *  Check that user exists
         */
        if ($this->existsId($id) === false) {
            throw new Exception('User does not exist');
        }

        /**
         *  Check that the actual password is valid
         */
        $this->checkUsernamePwd($id, $actualPassword);

        /**
         *  Now checking that actual password matches actual password in database
         */

        /**
         *  Get actual hashed password in database
         */
        $actualPasswordHashed = $this->getHashedPasswordFromDb($id);

        /**
         *  If result is empty then it is anormal
         */
        if (empty($actualPasswordHashed)) {
            throw new Exception('An error occurred while checking user password');
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
        $this->updatePassword($id, $newPasswordHashed);

        History::set('Password modification');
    }

    /**
     *  Reset specified user password
     */
    public function resetPassword(string $id) : string
    {
        if (!IS_ADMIN) {
            throw new Exception('You are not allowed to execute this action.');
        }

        if (!$this->existsId($id)) {
            throw new Exception('Specified user does not exist');
        }

        /**
         *  Get username
         */
        $username = $this->getUsernameById($id);

        /**
         *  Get role
         */
        $role = $this->getRoleById($id);

        if (empty($username)) {
            throw new Exception('Specified user does not exist');
        }

        /**
         *  Super-administrator password cannot be reset
         */
        if ($role == 1) {
            throw new Exception('You are not allowed to reset a super-administrator password');
        }

        /**
         *  If the current user is not a superadmin (he's only an admin), then he cannot reset password of another admin
         */
        // if (!IS_SUPERADMIN) {
        //     if ($role == 2) {
        //         throw new Exception('You are not allowed to reset password of another administrator');
        //     }
        // }

        /**
         *  Generating a new password
         */
        $password = $this->generateRandomPassword();

        /**
         *  Hashing password with salt
         */
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        if ($hashedPassword === false) {
            throw new Exception('Error while creating a new password for user ' . $username);
        }

        /**
         *  Adding new hashed password in database
         */
        $this->updatePassword($id, $hashedPassword);

        History::set('Reseted password of user <code>' . $username . '</code>');

        /**
         *  Return new password
         */
        return $password;
    }

    /**
     *  Update user role
     */
    public function updateRole(int $id, int $role) : void
    {
        $this->model->updateRole($id, $role);
    }
}
