<?php

namespace Controllers\Api\Authentication;

use Exception;

class Authentication
{
    private $loginController;
    private $hostController;
    private $apiKeyAuthentication = false;
    private $hostAuthentication = false;

    public function __construct()
    {
        $this->loginController = new \Controllers\Login();
        $this->hostController = new \Controllers\Host();
    }

    public function getApiKeyAuthenticationStatus()
    {
        return $this->apiKeyAuthentication;
    }

    public function getHostAuthenticationStatus()
    {
        return $this->hostAuthentication;
    }

    /**
     *  Check if authentication is valid
     *  It can be an API key authentication or a host authId and token authentication
     */
    public function valid(object $data)
    {
        /**
         *  If API key is specified
         */
        if (!empty($data->apikey)) {
            $apiKey = $data->apikey;
        }

        /**
         *  If host authId and token are specified
         */
        if (!empty($data->id)) {
            $id = $data->id;
        }
        if (!empty($data->token)) {
            $token = $data->token;
        }

        /**
         *  If no API key or host authId and token are specified
         */
        if (empty($apiKey) and (empty($id) or empty($token))) {
            return false;
        }

        /**
         *  If API key is specified, check that it is valid
         */
        if (!empty($apiKey)) {
            /**
             *  Check if API key exists
             */
            if (!$this->loginController->apiKeyValid($apiKey)) {
                return false;
            }

            /**
             *  Set apiKeyAuthentication to true if API key is valid
             */
            $this->apiKeyAuthentication = true;
        }

        /**
         *  If a host authId and token have been specified, check if they are valid
         */
        if (!empty($id) and !empty($token)) {
            $this->hostController->setAuthId($id);
            $this->hostController->setToken($token);

            if (!$this->hostController->checkIdToken()) {
                return false;
            }

            /**
             *  Set hostAuthentication to true if host authId and token are valid
             */
            $this->hostAuthentication = true;
        }

        return true;
    }
}
