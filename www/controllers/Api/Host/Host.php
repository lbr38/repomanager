<?php

namespace Controllers\Api\Host;

use Controllers\Host\Package\Package as HostPackage;
use Controllers\Host\Registering as HostRegistering;
use Controllers\Host\Host as HostController;
use Controllers\Host\Update as HostUpdate;
use Controllers\User\Permission\Host as HostPermission;
use Exception;

class Host extends \Controllers\Api\Controller
{
    public function execute(): array
    {
        $hostController = new HostController();
        $hostRegisteringController = new HostRegistering();
        $hostUpdateController = new HostUpdate();

        /**
         *  If a component is specified
         *  https://repomanager.mydomain.net/api/v2/host/
         */
        if (!empty($this->uri[4])) {
            if (is_numeric($this->uri[4])) {
                // Get host ID from URI
                $id = (int)$this->uri[4];

                // Check if host exists
                if (!$hostController->existsId($id)) {
                    throw new Exception('Host #' . $id . ' does not exist.');
                }

                // A host can only retrieve its own data, a user must have permissions on hosts
                if (defined('HOST_AUTH_ID')) {
                    if ($hostController->getIdByAuth(HOST_AUTH_ID) != $id) {
                        throw new Exception('You are not allowed to access this host');
                    }
                } else if (!defined('API_KEY') or !HostPermission::allowed()) {
                    throw new Exception('You are not allowed to access this host');
                }

                /**
                 *  Get installed packages for a host
                 *  https://repomanager.mydomain.net/api/v2/host/{id}/installed
                 */
                if ($this->uri[5] == 'installed' and $this->method == 'GET') {
                    $hostPackageController = new HostPackage($id);
                    return ['results' => $hostPackageController->getInstalled()];
                }

                /**
                 *  Get available updates for a host
                 *  https://repomanager.mydomain.net/api/v2/host/{id}/updates
                 */
                if (in_array($this->uri[5], ['available', 'updates']) and $this->method == 'GET') {
                    $hostPackageController = new HostPackage($id);
                    return ['results' => $hostPackageController->getAvailable()];
                }
            }

            /**
             *  Register a new host
             *  https://repomanager.mydomain.net/api/v2/host/registering
             */
            if ($this->uri[4] == 'registering' and $this->method == 'POST') {
                // Only administrators and users allowed to register hosts can register a host (API key authentication)
                if (!defined('API_KEY') or !HostPermission::allowedAction('register')) {
                    throw new Exception('You are not allowed to register a host');
                }

                // Try registering the host
                $result = $hostRegisteringController->register($this->data['ip'], $this->data['hostname']);

                // If register is successful, then return generated id and token
                return [
                    'rc' => 201,
                    'message' => ['Host successfully registered.'],
                    'results' => [
                        'id' => $result['authId'],
                        'token' => $result['token']
                    ]
                ];
            }

            // Retrieve host database Id if its authId has been specified, it will be useful for next tasks
            if (defined('HOST_AUTH_ID')) {
                try {
                    // Get host database Id from its authId
                    $this->hostId = $hostController->getIdByAuth(HOST_AUTH_ID);

                    if (empty($this->hostId)) {
                        throw new Exception('No host Id has been found from this authId identifier');
                    }

                    // Package controller will be useful for packages operations
                    $hostPackageController = new \Controllers\Host\Package\Package($this->hostId);
                    $hostEventController = new \Controllers\Host\Package\Event($this->hostId);
                } catch (Exception $e) {
                    throw new Exception('Coult not retrieve host Id in database');
                }
            }

            /**
             *  Unregister a host
             *  https://repomanager.mydomain.net/api/v2/host/registering
             */
            if ($this->uri[4] == 'registering' and $this->method == 'DELETE') {
                // Using authId and token authentication (host Id required)
                if (defined('HOST_AUTH_ID') and !empty($this->hostId)) {
                    $hostController->deleteById($this->hostId);
                // Using Api key authentication (hostname required)
                } else if (defined('API_KEY') and !empty($this->data['hostname'])) {
                    if (!HostPermission::allowedAction('delete')) {
                        throw new Exception('You are not allowed to delete a host');
                    }

                    $hostController->deleteByHostname($this->data['hostname']);
                } else {
                    throw new Exception('To unregister a host, you must use either host authId+token authentication or Api key authentication with hostname specified.');
                }

                return ['message' => ['Host has been deleted.']];
            }

            /**
             *  Following requests are only available if host authentication is valid and
             *  database Id has been retrieved
             */
            if (defined('HOST_AUTH_ID') and !empty($this->hostId)) {
                /**
                 *  Host sends general information and status
                 *  https://repomanager.mydomain.net/api/v2/host/status
                 */
                if ($this->uri[4] == 'status' and $this->method == 'PUT') {
                    $updated = $hostUpdateController->updateStatus($this->hostId, $this->data ?? []);

                    return ['message' => array_map(fn ($label) => $label . ' updated successfully.', array_values($updated))];
                }

                /**
                 *  Host send package information
                 *  https://repomanager.mydomain.net/api/v2/host/packages
                 */
                if ($this->uri[4] == 'packages' and $this->method == 'PUT') {
                    /**
                     *  If installed packages list has been specified then update it in database
                     *  https://repomanager.mydomain.net/api/v2/host/packages/installed
                     */
                    if ($this->uri[5] == 'installed' and $this->method == 'PUT') {
                        if (isset($this->data['installed_packages'])) {
                            try {
                                $hostPackageController->setPackagesInventory($this->data['installed_packages']);
                            } catch (Exception $e) {
                                throw new Exception('Installed packages update has failed: ' . $e->getMessage());
                            }

                            return ['message' => ['Installed packages updated successfully.']];
                        }
                    }

                    /**
                     *  If available packages list has been specified then update it in database
                     *  https://repomanager.mydomain.net/api/v2/host/packages/available
                     */
                    if ($this->uri[5] == 'available' and $this->method == 'PUT') {
                        if (isset($this->data['available_packages'])) {
                            try {
                                $hostPackageController->setPackagesAvailable($this->data['available_packages']);
                            } catch (Exception $e) {
                                throw new Exception('Available packages update has failed: ' . $e->getMessage());
                            }
                        }

                        return ['message' => ['Available packages updated successfully.']];
                    }

                    /**
                     *  If packages events list has been specified then update it in database
                     *  https://repomanager.mydomain.net/api/v2/host/packages/event
                     */
                    if ($this->uri[5] == 'event' and $this->method == 'PUT') {
                        // If packages events history has been specified then update it in database
                        if (!empty($this->data['events'])) {
                            try {
                                $hostEventController->setHistory($this->data['events']);
                            } catch (Exception $e) {
                                throw new Exception('Package events history update has failed: ' . $e->getMessage());
                            }

                            return ['message' => ['Package events history updated successfully.']];
                        }
                    }
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
