<?php

namespace Controllers\Api\Profile;

use Exception;
use Datetime;

class Profile extends \Controllers\Api\Controller
{
    private $profile;
    private $component;

    public function execute()
    {
        $myprofile = new \Controllers\Profile();

        /**
         *  Retrieve profile and component from URI
         */
        if (isset($this->uri[4])) {
            $this->profile = $this->uri[4];
        }
        if (isset($this->uri[5])) {
            $this->component = $this->uri[5];
        }

        /**
         *  https://repomanager.mydomain.net/api/v2/profile/
         *  Print all profiles
         */
        if (empty($this->profile) and $this->method == 'GET') {
            return array('results' => $myprofile->listName());
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
                return array('results' => array($myprofile->getServerConfiguration()));
            }

            /**
             *  Return profile main configuration
             */
            if (empty($this->component)) {
                return array('results' => array($myprofile->getProfileConfiguration($this->profile)));
            }

            /**
             *  Return profile packages excludes
             */
            if ($this->component == 'excludes') {
                return array('results' => array($myprofile->getProfilePackagesConfiguration($this->profile)));
            }

            /**
             *  Return profile repos configuration
             */
            if ($this->component == 'repos') {
                return array('results' => $myprofile->getReposMembersList($this->profile));
            }
        }

        throw new Exception('Invalid request');
    }
}
