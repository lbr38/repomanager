<?php

namespace Controllers\Host;

use Exception;
use Datetime;

class Execute extends \Controllers\Host
{
    public function installSelectedAvailablePackages(int $hostId, array $packages)
    {
        /**
         *  Retrieve hostname by host id
         */
        $hostname = $this->getHostnameById($hostId);

        /**
         *  Check that $packages is not empty
         */
        if (empty($packages)) {
            throw new Exception('No packages selected');
        }

        /**
         *  Add a new request to the database
         */
        $this->newWsRequest($hostId, 'request-specific-packages-installation', $packages);

        return 'Requesting installation of selected packages on host ' . $hostname;
    }
}
