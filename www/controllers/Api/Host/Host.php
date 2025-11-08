<?php

namespace Controllers\Api\Host;

use Exception;

class Host extends \Controllers\Api\Controller
{
    private $component;
    private $action;
    private $ip = null;
    private $hostname = null;

    public function execute()
    {
        $myhost = new \Controllers\Host();
        $hostUpdateController = new \Controllers\Host\Update();

        /**
         *  Retrieve component and action from URI
         */
        if (isset($this->uri[4])) {
            $this->component = $this->uri[4];
        }
        if (isset($this->uri[5])) {
            $this->action = $this->uri[5];
        }

        /**
         *  Retrieve ip and hostname from data sent
         */
        if (!empty($this->data->ip)) {
            $this->ip = $this->data->ip;
        }
        if (!empty($this->data->hostname)) {
            $this->hostname = $this->data->hostname;
        }

        /**
         *  If no component is specified, return all active hosts
         *  This request is only available with a valid API key authentication, not for hosts id+token authentication
         *  https://repomanager.mydomain.net/api/v2/host/
         */
        if (empty($this->component)) {
            if (defined('API_KEY')) {
                /**
                 *  Get list of all active hosts
                 *  https://repomanager.mydomain.net/api/v2/host/
                 */
                if ($this->method == 'GET') {
                    try {
                        $list = $myhost->listAll();
                    } catch (Exception $e) {
                        throw new Exception('Hosts listing has failed.');
                    }

                    return ['results' => $list];
                }
            }
        }

        /**
         *  If a component is specified
         *  https://repomanager.mydomain.net/api/v2/host/
         */
        if (!empty($this->component)) {
            /**
             *  Register a new host
             *  https://repomanager.mydomain.net/api/v2/host/registering
             */
            if ($this->component == 'registering' and $this->method == 'POST') {
                /**
                 *  Try registering the host
                 */
                $result = $myhost->register($this->ip, $this->hostname);

                /**
                 *  If register is successful, then return generated id and token
                 */
                return [
                    'message' => ['Host successfully registered.'],
                    'results' => [
                        'id' => $result['authId'],
                        'token' => $result['token']
                    ]
                ];
            }

            /**
             *  Retrieve host database Id if its authId has been specified, it will be useful for next tasks
             */
            if (defined('HOST_AUTH_ID')) {
                try {
                    // Get host database Id from its authId
                    $this->hostId = $myhost->getIdByAuth(HOST_AUTH_ID);

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
            if ($this->component == 'registering' and $this->method == 'DELETE') {
                // Using authId and token authentication (host Id required)
                if (defined('HOST_AUTH_ID') and !empty($this->hostId)) {
                    $myhost->deleteById($this->hostId);
                // Using Api key authentication (hostname required)
                } else if (defined('API_KEY') and !empty($this->hostname)) {
                    $myhost->deleteByHostname($this->hostname);
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
                 *  Host sends general informations and status
                 *  https://repomanager.mydomain.net/api/v2/host/status
                 */
                if ($this->component == 'status' and $this->method == 'PUT') {
                    $message = [];

                    /**
                     *  If hostname has been specified then update it in database
                     */
                    if (!empty($this->hostname)) {
                        try {
                            $hostUpdateController->updateHostname($this->hostId, $this->hostname);
                            $message[] = 'Hostname updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('Hostname update has failed.');
                        }
                    }

                    /**
                     *  If OS has been specified then update it in database
                     */
                    if (!empty($this->data->os)) {
                        try {
                            $hostUpdateController->updateOS($this->hostId, $this->data->os);
                            $message[] = 'OS updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('OS update has failed.');
                        }
                    }

                    /**
                     *  If OS release version has been specified then update it in database
                     */
                    if (!empty($this->data->os_version)) {
                        try {
                            $hostUpdateController->updateOsVersion($this->hostId, $this->data->os_version);
                            $message[] = 'OS version updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('OS version update has failed.');
                        }
                    }

                    /**
                     *  If OS family has been specified then update it in database
                     */
                    if (!empty($this->data->os_family)) {
                        try {
                            $hostUpdateController->updateOsFamily($this->hostId, $this->data->os_family);
                            $message[] = 'OS family updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('OS family update has failed.');
                        }
                    }

                    /**
                     *  If virtualization type has been specified then update it in database
                     */
                    if (!empty($this->data->type)) {
                        try {
                            $hostUpdateController->updateType($this->hostId, $this->data->type);
                            $message[] = 'Virtualization type updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('Virtualization type update has failed.');
                        }
                    }

                    /**
                     *  If CPU has been specified then update it in database
                     */
                    if (!empty($this->data->cpu)) {
                        try {
                            $hostUpdateController->updateCpu($this->hostId, $this->data->cpu);
                            $message[] = 'CPU updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('CPU update has failed.');
                        }
                    }

                    /**
                     *  If RAM has been specified then update it in database
                     */
                    if (!empty($this->data->ram)) {
                        try {
                            $hostUpdateController->updateRam($this->hostId, $this->data->ram);
                            $message[] = 'RAM updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('RAM update has failed.');
                        }
                    }

                    /**
                     *  If kernel has been specified then update it in database
                     */
                    if (!empty($this->data->kernel)) {
                        try {
                            $hostUpdateController->updateKernel($this->hostId, $this->data->kernel);
                            $message[] = "Kernel updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Kernel update has failed.');
                        }
                    }

                    /**
                     *  If architecture has been specified then update it in database
                     */
                    if (!empty($this->data->arch)) {
                        try {
                            $hostUpdateController->updateArch($this->hostId, $this->data->arch);
                            $message[] = "Arch updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Arch update has failed.');
                        }
                    }

                    /**
                     *  If profile has been specified then update it in database
                     */
                    if (!empty($this->data->profile)) {
                        try {
                            $hostUpdateController->updateProfile($this->hostId, $this->data->profile);
                            $message[] = "Profile updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Profile update has failed.');
                        }
                    }

                    /**
                     *  If environment has been specified then update it in database
                     */
                    if (!empty($this->data->env)) {
                        try {
                            $hostUpdateController->updateEnv($this->hostId, $this->data->env);
                            $message[] = "Environment updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Environment update has failed.');
                        }
                    }

                    /**
                     *  If agent status has been specified then update it in database
                     */
                    if (!empty($this->data->agent_status)) {
                        try {
                            $hostUpdateController->updateAgentStatus($this->hostId, $this->data->agent_status);
                            $message[] = "Agent status updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Agent status update has failed.');
                        }
                    }

                    /**
                     *  If linupdate version has been specified then update it in database
                     */
                    if (!empty($this->data->linupdate_version)) {
                        try {
                            $hostUpdateController->updateLinupdateVersion($this->hostId, $this->data->linupdate_version);
                            $message[] = "Linupdate version updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Linupdate version update has failed.');
                        }
                    }

                    /**
                     *  If reboot required status has been specified then update it in database
                     */
                    if (!empty($this->data->reboot_required)) {
                        try {
                            $hostUpdateController->updateRebootRequired($this->hostId, $this->data->reboot_required);
                            $message[] = "Reboot status updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Reboot status update has failed.');
                        }
                    }

                    /**
                     *  If uptime has been specified then update it in database
                     */
                    if (!empty($this->data->uptime)) {
                        try {
                            $hostUpdateController->updateUptime($this->hostId, $this->data->uptime);
                            $message[] = "Uptime updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Uptime update has failed.');
                        }
                    }

                    return ['message' => $message];
                }

                /**
                 *  Host send packages informations
                 *  https://repomanager.mydomain.net/api/v2/host/packages
                 */
                if ($this->component == 'packages' and $this->method == 'PUT') {
                    /**
                     *  If installed packages list has been specified then update it in database
                     *  https://repomanager.mydomain.net/api/v2/host/packages/installed
                     */
                    if ($this->action == 'installed' and $this->method == 'PUT') {
                        if (isset($this->data->installed_packages)) {
                            try {
                                $hostPackageController->setPackagesInventory($this->data->installed_packages);
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
                    if ($this->action == 'available' and $this->method == 'PUT') {
                        if (isset($this->data->available_packages)) {
                            try {
                                $hostPackageController->setPackagesAvailable($this->data->available_packages);
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
                    if ($this->action == 'event' and $this->method == 'PUT') {
                        /**
                         *  If packages events history has been specified then update it in database
                         */
                        if (!empty($this->data->events)) {
                            try {
                                $hostEventController->setHistory($this->data->events);
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
