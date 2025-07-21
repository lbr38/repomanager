<?php

namespace Controllers\App;

use Exception;

class Permissions
{
    /**
     *  Load user permissions if the user is not an administrator
     */
    public static function load()
    {
        if (IS_ADMIN) {
            /**
             *  Delete any existing user permissions cookie because admin has all permissions
             *  This could happen if the user was a standard user and then became an administrator
             */
            if (isset($_COOKIE['user_permissions'])) {
                setcookie('user_permissions', '', time() - 3600, '/');
            }

            return;
        }

        if (!defined('USER_PERMISSIONS')) {
            try {
                $userController = new \Controllers\User\User();
                $userPermissionControler = new \Controllers\User\Permission();

                // Check if user exists
                if (!$userController->existsId($_SESSION['id'])) {
                    throw new Exception('User with ID #' . $_SESSION['id'] . ' does not exist.');
                }

                // Get and define user permissions
                define('USER_PERMISSIONS', $userPermissionControler->get($_SESSION['id']));

                // Also define a cookie with user permissions
                setcookie('user_permissions', json_encode(USER_PERMISSIONS), [
                    'expires' => time() + 86400, // 1 day
                    'path' => '/',
                    'secure' => true,
                ]);
            } catch (Exception $e) {
                throw new Exception('Error getting user #' . $_SESSION['id'] . ' permissions: ' . $e->getMessage());
            }

            unset($userController, $userPermissionControler);
        }
    }
}
