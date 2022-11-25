<?php

namespace Controllers\Api;

use Exception;
use Datetime;

class Profile
{
    private $profileController;

    public function __construct()
    {
        $this->profileController = new \Controllers\Profile();
    }

    public function execute()
    {
        $component = '';
        $profile = '';

        if (empty($_SERVER['REQUEST_URI']) or empty($_SERVER['REQUEST_METHOD'])) {
            throw new Exception('Error while parsing requested URL');
        }

        /**
         *  Get sended data if any
         */
        $data = json_decode(file_get_contents("php://input"));

        /**
         *  Parse URL
         */
        $url = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($url, PHP_URL_PATH);
        $uri = explode('/', $uri);
        if (isset($uri[4])) {
            $profile = $uri[4];
        }
        if (isset($uri[5])) {
            $component = $uri[5];
        }

        /**
         *  If $profile == 'server-settings' then return server configuration
         */
        if ($profile == 'server-settings') {
            return array('results' => array($this->profileController->getServerConfiguration()));
        }

        /**
         *  If id or token are empty
         */
        if (empty($data->id) or empty($data->token)) {
            Api::returnAuthenticationRequired();
        }

        /**
         *  Check id and token
         */
        if (!Api::checkHostAuthentication($data->id, $data->token)) {
            Api::returnBadCredentials();
        }

        /**
         *  http://127.0.0.1/api/v2/profile/
         *  Print all profiles
         */
        if (empty($profile) and $method == 'GET') {
            return array('results' => $this->profileController->listName());
        }

        /**
         *  If a profile name is specified
         *  http://127.0.0.1/api/v2/profile/$profile
         */
        if (!empty($profile) and $method == 'GET') {
            /**
             *  Return profile main configuration
             */
            if (empty($component)) {
                return array('results' => array($this->profileController->getProfileConfiguration($profile)));
            }

            /**
             *  Return profile packages excludes
             */
            if ($component == 'excludes') {
                return array('results' => array($this->profileController->getProfilePackagesConfiguration($profile)));
            }

            /**
             *  Return profile repos configuration
             */
            if ($component == 'repos') {
                return array('results' => $this->profileController->getReposMembersList($profile));
            }
        }

        throw new Exception('Invalid request');
    }
}
