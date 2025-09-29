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
        $userController = new \Controllers\User\User();
        $userPermissionController = new \Controllers\User\Permission();
        $isAdmin = false;
        $isSuperAdmin = false;

        /**
         *  Case a session is started
         */
        if (isset($_SESSION)) {
            // Check if session is valid
            if (empty($_SESSION['id'])) {
                throw new Exception('Session id is empty.');
            }

            if (empty($_SESSION['role'])) {
                throw new Exception('Session role is empty.');
            }

            $id   = $_SESSION['id'];
            $role = $_SESSION['role'];

            // Define IS_ADMIN
            if ($role === 'super-administrator' or $role === 'administrator') {
                $isAdmin = true;
            }

            // Define IS_SUPERADMIN
            if (isset($role) and $role === 'super-administrator') {
                $isSuperAdmin = true;
            }

            // If the user is an administrator
            if ($isAdmin) {
                /**
                 *  Delete any existing user permissions cookie because admin has all permissions
                 *  This could happen if the user was a standard user and then became an administrator
                 */
                if (isset($_COOKIE['user_permissions'])) {
                    setcookie('user_permissions', '', time() - 3600, '/');
                }
            }

            // If the user is not an admin, get user permissions
            if (!$isAdmin and !defined('USER_PERMISSIONS')) {
                try {
                    // Check if user exists
                    if (!$userController->existsId($id)) {
                        throw new Exception('User with ID #' . $id . ' does not exist.');
                    }

                    // Get and define user permissions
                    define('USER_PERMISSIONS', $userPermissionController->get($id));

                    // Also define a cookie with user permissions
                    setcookie('user_permissions', json_encode(USER_PERMISSIONS), [
                        'expires' => time() + 86400, // 1 day
                        'path' => '/',
                        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                    ]);
                } catch (Exception $e) {
                    throw new Exception('Error getting user #' . $id . ' permissions: ' . $e->getMessage());
                }
            }
        }

        /**
         *  Case no session is started but user is authenticated (through API key)
         */
        if (defined('AUTHENTICATED') and AUTHENTICATED === true) {
            // Case user is authenticated through API key
            if (defined('API_KEY')) {
                // Check if API key is an admin API key
                if ($userController->apiKeyIsAdmin(API_KEY)) {
                    $isAdmin = true;
                // If not an admin API key, get user permissions
                } else {
                    // Get user Id from API key
                    $id = $userController->getIdByApiKey(API_KEY);

                    if ($id === null) {
                        throw new Exception('Invalid API key');
                    }

                    // Get and define user permissions
                    define('USER_PERMISSIONS', $userPermissionController->get($id));
                }
            }
        }

        // Define IS_ADMIN
        if (!defined('IS_ADMIN')) {
            define('IS_ADMIN', $isAdmin);
        }

        // Define IS_SUPERADMIN
        if (!defined('IS_SUPERADMIN')) {
            define('IS_SUPERADMIN', $isSuperAdmin);
        }

        unset($userController, $userPermissionController, $id, $role);
    }
}
