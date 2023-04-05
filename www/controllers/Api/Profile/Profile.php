<?php

namespace Controllers\Api\Profile;

use Exception;
use Datetime;

class Profile
{
    private $profileController;
    private $method;
    private $profile;
    private $component;
    private $data;
    private $apiKeyAuthentication = false;
    private $hostAuthentication = false;

    public function __construct(string $method, array $uri, object $data)
    {
        $this->profileController = new \Controllers\Profile();
        $this->method = $method;

        /**
         *  Retrive profile and component from URI
         */
        if (isset($uri[4])) {
            $this->profile = $uri[4];
        }
        if (isset($uri[5])) {
            $this->component = $uri[5];
        }

        $this->data = $data;
    }

    public function setApiKeyAuthentication(bool $apiKeyAuthentication)
    {
        $this->apiKeyAuthentication = $apiKeyAuthentication;
    }

    public function setHostAuthentication(bool $hostAuthentication)
    {
        $this->hostAuthentication = $hostAuthentication;
    }

    public function execute()
    {
        /**
         *  https://repomanager.mydomain.net/api/v2/profile/
         *  Print all profiles
         */
        if (empty($this->profile) and $this->method == 'GET') {
            return array('results' => $this->profileController->listName());
        }

        /**
         *  If a profile name is specified
         *  https://repomanager.mydomain.net/api/v2/profile/$this->profile
         */
        if (!empty($this->profile) and $this->method == 'GET') {
            /**
             *  If $this->profile == 'server-settings' then return server configuration
             */
            if ($this->profile == 'server-settings') {
                return array('results' => array($this->profileController->getServerConfiguration()));
            }

            /**
             *  Return profile main configuration
             */
            if (empty($this->component)) {
                return array('results' => array($this->profileController->getProfileConfiguration($this->profile)));
            }

            /**
             *  Return profile packages excludes
             */
            if ($this->component == 'excludes') {
                return array('results' => array($this->profileController->getProfilePackagesConfiguration($this->profile)));
            }

            /**
             *  Return profile repos configuration
             */
            if ($this->component == 'repos') {
                return array('results' => $this->profileController->getReposMembersList($this->profile));
            }
        }

        throw new Exception('Invalid request');
    }
}
