<?php

namespace Controllers\App;

use Exception;

/**
 *  Handles authentication for the application using API keys or host Id and token.
 */
class Header
{
    public static function authenticate()
    {
        $userController = new \Controllers\User\User();
        $hostController = new \Controllers\Host();

        try {
            if (empty($_SERVER['HTTP_AUTHORIZATION'])) {
                throw new Exception('HTTP Authorization header is missing');
            }

            /**
             *  If API key or host Id+token is specified through the Authorization header
             *  e.g.
             *      "Authorization: Bearer <API_KEY>"
             *      "Authorization: Host <HOST_ID>:<HOST_TOKEN>"
             */
            if (strpos($_SERVER['HTTP_AUTHORIZATION'], 'Bearer ') === 0) {
                /**
                 *  Extract the token
                 *  Remove "Bearer " from the header
                 */
                $apiKey = substr($_SERVER['HTTP_AUTHORIZATION'], 7);

                if (!$userController->apiKeyValid($apiKey)) {
                    throw new Exception('Invalid API key');
                }

                define('API_KEY', $apiKey);
            }

            /**
             *  If host Id+token are specified through the Authorization header
             */
            if (strpos($_SERVER['HTTP_AUTHORIZATION'], 'Host ') === 0) {
                /**
                 *  Extract the host Id and token
                 *  Remove "Host " from the header
                 */
                $hostIdToken = substr($_SERVER['HTTP_AUTHORIZATION'], 5);

                /**
                 *  Split the host Id and token
                 */
                $hostIdToken = explode(':', $hostIdToken);

                /**
                 *  Check if host Id and token are specified
                 */
                if (count($hostIdToken) != 2) {
                    throw new Exception('Invalid host Id and token format');
                }

                /**
                 *  Check if host Id and token are valid
                 */
                if (!$hostController->checkIdToken($hostIdToken[0], $hostIdToken[1])) {
                    throw new Exception('Invalid host Id and token');
                }

                /**
                 *  Set host authId and token
                 */
                define('HOST_AUTH_ID', $hostIdToken[0]);
                define('HOST_TOKEN', $hostIdToken[1]);
            }

            define('AUTHENTICATED', true);
        } catch (Exception $e) {
            define('AUTHENTICATED', false);
            define('AUTHENTICATION_ERROR', $e->getMessage());
        }
    }
}
