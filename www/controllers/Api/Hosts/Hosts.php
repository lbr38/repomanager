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

                // Use strict filters if strict params are set
                $strictName = isset($_GET['strict-name']) ?? false;
                $strictVersion = isset($_GET['strict-version']) ?? false;

                // If the "strict" query parameter is set, both strictName and strictVersion will be true
                if (isset($_GET['strict'])) {
                    $strictName = true;
                    $strictVersion = true;
                }

                // If the "absent" query parameter is set, return hosts on which the package is NOT installed
                $absent = isset($_GET['absent']);

                if (empty($this->uri[5])) {
                    throw new Exception('You must specify a package');
                }

                return ['results' => $hostListingController->getByPackage($this->uri[5], $version, $strictName, $strictVersion, $absent)];
            }

            /**
             *  List up-to-date hosts (0 available updates)
             *  https://repomanager.mydomain.net/api/v2/hosts/uptodate
             */
            if ($this->uri[4] == 'uptodate' and $this->method == 'GET') {
                return ['results' => $hostListingController->getUpToDate()];
            }

            /**
             *  List compliant hosts (all compliance criteria)
             *  https://repomanager.mydomain.net/api/v2/hosts/compliant
             */
            if ($this->uri[4] == 'compliant' and $this->method == 'GET') {
                return ['results' => $hostListingController->getCompliant(isset($_GET['packages']))];
            }

            /**
             *  List outdated hosts (at least 1 available update)
             *  https://repomanager.mydomain.net/api/v2/hosts/outdated
             */
            if ($this->uri[4] == 'outdated' and $this->method == 'GET') {
                // Also get the list of available updates if the "packages" query parameter is set
                return ['results' => $hostListingController->getOutdated(isset($_GET['packages']))];
            }

            /**
             *  List non-compliant hosts (all compliance criteria)
             *  https://repomanager.mydomain.net/api/v2/hosts/non-compliant
             */
            if ($this->uri[4] == 'non-compliant' and $this->method == 'GET') {
                // Also get the list of available updates if the "packages" query parameter is set
                return ['results' => $hostListingController->getNonCompliant(isset($_GET['packages']))];
            }
        }

        throw new Exception('Invalid request');
    }
}
