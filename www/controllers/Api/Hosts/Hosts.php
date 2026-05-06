<?php

namespace Controllers\Api\Hosts;

use Controllers\Host\Listing;
use Exception;

class Hosts extends \Controllers\Api\Controller
{
    public function execute(): array
    {
        $hostListingController = new Listing();

        /**
         *  List all hosts
         *  https://repomanager.mydomain.net/api/v2/hosts/
         */
        if (empty($this->uri[4]) and $this->method == 'GET') {
            return ['results' => $hostListingController->get()];
        }

        if (!empty($this->uri[4])) {
            /**
             *  List hosts by OS and OS version (optional)
             *  https://repomanager.mydomain.net/api/v2/hosts/os/{os}/{os_version?}
             */
            if ($this->uri[4] == 'os' and $this->method == 'GET') {
                $osVersion = $this->uri[6] ?? '';

                if (empty($this->uri[5])) {
                    throw new Exception('You must specify an OS');
                }

                return ['results' => $hostListingController->getByOs($this->uri[5], $osVersion)];
            }

            /**
             *  List hosts by kernel
             *  https://repomanager.mydomain.net/api/v2/hosts/kernel/{kernel}
             */
            if ($this->uri[4] == 'kernel' and $this->method == 'GET') {
                if (empty($this->uri[5])) {
                    throw new Exception('You must specify a kernel');
                }

                return ['results' => $hostListingController->getByKernel($this->uri[5])];
            }

            /**
             *  List hosts by architecture
             *  https://repomanager.mydomain.net/api/v2/hosts/arch/{arch}
             */
            if ($this->uri[4] == 'arch' and $this->method == 'GET') {
                if (empty($this->uri[5])) {
                    throw new Exception('You must specify an architecture');
                }

                return ['results' => $hostListingController->getByArch($this->uri[5])];
            }

            /**
             *  List hosts by profile
             *  https://repomanager.mydomain.net/api/v2/hosts/profile/{profile}
             */
            if ($this->uri[4] == 'profile' and $this->method == 'GET') {
                if (empty($this->uri[5])) {
                    throw new Exception('You must specify a profile');
                }

                return ['results' => $hostListingController->getByProfile($this->uri[5])];
            }

            /**
             *  List hosts by environment
             *  https://repomanager.mydomain.net/api/v2/hosts/environment/{environment}
             */
            if ($this->uri[4] == 'environment' and $this->method == 'GET') {
                if (empty($this->uri[5])) {
                    throw new Exception('You must specify an environment');
                }

                return ['results' => $hostListingController->getByEnvironment($this->uri[5])];
            }

            /**
             *  List hosts by package
             *  https://repomanager.mydomain.net/api/v2/hosts/package/{package}/{version?}
             */
            if ($this->uri[4] == 'package' and $this->method == 'GET') {
                $version = $this->uri[6] ?? '';

                if (empty($this->uri[5])) {
                    throw new Exception('You must specify a package');
                }

                return ['results' => $hostListingController->getByPackage($this->uri[5], $version)];
            }

            /**
             *  List up-to-date hosts
             *  https://repomanager.mydomain.net/api/v2/hosts/uptodate
             */
            if ($this->uri[4] == 'uptodate' and $this->method == 'GET') {
                return ['results' => $hostListingController->getUpToDate()];
            }

            /**
             *  List outdated hosts
             *  https://repomanager.mydomain.net/api/v2/hosts/outdated
             */
            if ($this->uri[4] == 'outdated' and $this->method == 'GET') {
                return ['results' => $hostListingController->getOutdated()];
            }
        }

        throw new Exception('Invalid request');
    }
}
