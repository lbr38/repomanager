<?php

namespace Controllers\Host;

use Exception;

class Registering extends Host
{
    /**
     *  Register a new host in the database
     */
    public function register(string $ip, string $hostname): array
    {
        if (empty($ip) or empty($hostname)) {
            throw new Exception('You must provide IP address and hostname');
        }

        // Check if the hostname already exists in the database
        if ($this->existsHostname($hostname) === true) {
            throw new Exception('Host ' . $hostname . ' is already registered');
        }

        /**
         *  Generate a new authId for this host
         *  This authId will be used to authenticate the host when it will try to connect to the API
         *  It must be unique so loop until we find a unique authId
         */
        $authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));

        /**
         *  It must be unique so loop until we find a unique authId
         *  We check if an host exist with the same authId
         */
        while (!empty($this->getIdByAuth($authId))) {
            $authId = 'id_' . bin2hex(openssl_random_pseudo_bytes(16));
        }

        // Generate a new token for this host
        $token = bin2hex(openssl_random_pseudo_bytes(16));

        // Add the host in database
        $this->add($ip, $hostname, $authId, $token, 'unknown', date('Y-m-d'), date('H:i:s'));

        // Retrieve the Id of the host added in the database
        $id = $this->getLastInsertRowID();

        // Create a dedicated directory for this host, based on its Id
        if (!mkdir(HOSTS_DIR . '/' . $id, 0770, true)) {
            throw new Exception('The server could not finalize registering: failed to create host directory');
        }

        return ['authId' => $authId, 'token' => $token];
    }
}
