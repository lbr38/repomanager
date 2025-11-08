<?php

namespace Controllers\Api\Profile;

use Exception;

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
            return ['results' => $myprofile->listName()];
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
                return ['results' => [$myprofile->getServerConfiguration()]];
            }

            /**
             *  Return profile main configuration
             */
            if (empty($this->component)) {
                return ['results' => [$myprofile->getProfileConfiguration($this->profile)]];
            }

            /**
             *  Return profile packages excludes
             */
            if ($this->component == 'excludes') {
                return ['results' => [$myprofile->getProfilePackagesConfiguration($this->profile)]];
            }

            /**
             *  Return profile repos configuration
             */
            if ($this->component == 'repos') {
                return ['results' => $myprofile->getReposMembersList($this->profile)];
            }
        }

        throw new Exception('Invalid request');
    }
}
