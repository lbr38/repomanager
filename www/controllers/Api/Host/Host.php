<?php

namespace Controllers\Api\Host;

use Exception;
use Datetime;

class Host
{
    private $hostController;
    private $method;
    private $component;
    private $action;
    private $data;
    private $apiKeyAuthentication = false;
    private $hostAuthentication = false;

    public function __construct(string $method, array $uri, object $data)
    {
        $this->hostController = new \Controllers\Host();
        $this->method = $method;

        /**
         *  Retrive component and action from URI
         */
        if (isset($uri[4])) {
            $this->component = $uri[4];
        }
        if (isset($uri[5])) {
            $this->action = $uri[5];
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
         *  Retrieve ip and hostname from data sent
         */
        if (!empty($this->data->ip)) {
            $this->hostController->setIp($this->data->ip);
        }
        if (!empty($this->data->hostname)) {
            $this->hostController->setHostname($this->data->hostname);
        }

        /**
         *  If no component is specified, return all active hosts
         *  This request is only available with a valid API key authentication, not for hosts id+token authentication
         *  https://repomanager.mydomain.net/api/v2/host/
         */
        if (empty($this->component)) {
            if ($this->apiKeyAuthentication === true) {
                /**
                 *  Get list of all active hosts
                 *  https://repomanager.mydomain.net/api/v2/host/
                 */
                if ($this->method == 'GET') {
                    try {
                        $list = $this->hostController->listAll();
                        return array('results' => $list);
                    } catch (Exception $e) {
                        throw new Exception('Hosts listing has failed.');
                    }
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
             *  Registering is the only request that does not require authentication as it is used to register a new host which does not have any authentication yet
             */
            if ($this->component == 'registering' and $this->method == 'POST') {
                /**
                 *  Try registering the host
                 */
                $this->hostController->register();

                /**
                 *  If register is successful, then return generated id and token
                 */
                return array('message' => array('Host successfully registered.'), 'results' => array('id' => $this->hostController->getAuthId(), 'token' => $this->hostController->getToken()));
            }

            /**
             *  Retrieve host database Id if its authId has been specified, it will be useful for next operations
             */
            if (!empty($this->data->id)) {
                try {
                    $this->hostController->setAuthId($this->data->id);
                    $this->hostController->setId($this->hostController->getIdByAuth());
                } catch (Exception $e) {
                    throw new Exception('Coult not retrieve host Id in database');
                }
            }

            /**
             *  Following requests are only available if host authentication is valid and
             *  database Id has been retrieved
             */
            if ($this->hostAuthentication === true and !empty($this->hostController->getId())) {
                /**
                 *  Unregister a host
                 *  https://repomanager.mydomain.net/api/v2/host/registering
                 */
                if ($this->component == 'registering' and $this->method == 'DELETE') {
                    $this->hostController->unregister();
                    return array('message' => array('Host has been deleted.'));
                }

                /**
                 *  Host sends general informations and status
                 *  https://repomanager.mydomain.net/api/v2/host/status
                 */
                if ($this->component == 'status' and $this->method == 'PUT') {
                    $message = array();

                    /**
                     *  If hostname has been specified then update it in database
                     */
                    if (!empty($this->data->hostname)) {
                        try {
                            $this->hostController->updateHostname($this->data->hostname);
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
                            $this->hostController->updateOS($this->data->os);
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
                            $this->hostController->updateOsVersion($this->data->os_version);
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
                            $this->hostController->updateOsFamily($this->data->os_family);
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
                            $this->hostController->updateType($this->data->type);
                            $message[] = 'Virtualization type updated successfully.';
                        } catch (Exception $e) {
                            throw new Exception('Virtualization type update has failed.');
                        }
                    }

                    /**
                     *  If kernel has been specified then update it in database
                     */
                    if (!empty($this->data->kernel)) {
                        try {
                            $this->hostController->updateKernel($this->data->kernel);
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
                            $this->hostController->updateArch($this->data->arch);
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
                            $this->hostController->updateProfile($this->data->profile);
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
                            $this->hostController->updateEnv($this->data->env);
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
                            $this->hostController->updateAgentStatus($this->data->agent_status);
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
                            $this->hostController->updateLinupdateVersion($this->data->linupdate_version);
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
                            $this->hostController->updateRebootRequired($this->data->reboot_required);
                            $message[] = "Reboot status updated successfully.";
                        } catch (Exception $e) {
                            throw new Exception('Reboot status update has failed.');
                        }
                    }

                    return array('message' => $message);
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
                        /**
                         *  If installed packages list has been specified then update it in database
                         */
                        if (!empty($this->data->installed_packages)) {
                            try {
                                $this->hostController->setPackagesInventory($this->data->installed_packages);
                                return array('message' => array('Installed packages updated successfully.'));
                            } catch (Exception $e) {
                                throw new Exception('Installed packages update has failed.');
                            }
                        }
                    }

                    /**
                     *  If available packages list has been specified then update it in database
                     *  https://repomanager.mydomain.net/api/v2/host/packages/available
                     */
                    if ($this->action == 'available' and $this->method == 'PUT') {
                        /**
                         *  If available packages list has been specified then update it in database
                         */
                        if (!empty($this->data->available_packages)) {
                            try {
                                $this->hostController->setPackagesAvailable($this->data->available_packages);
                                return array('message' => array('Available packages updated successfully.'));
                            } catch (Exception $e) {
                                throw new Exception('Available packages update has failed.');
                            }
                        }
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
                                $this->hostController->setEventsFullHistory($this->data->events);
                                return array('message' => array('Package events history updated successfully.'));
                            } catch (Exception $e) {
                                throw new Exception('Package events history update has failed.');
                            }
                        }
                    }
                }

                /**
                 *  Host request acknowledgement
                 *  https://repomanager.mydomain.net/api/v2/host/request
                 */
                if ($this->component == 'request' and $this->method == 'PUT') {
                    /**
                     *  https://repomanager.mydomain.net/api/v2/host/request/packages-update
                     *  https://repomanager.mydomain.net/api/v2/host/request/general-status-update
                     *  https://repomanager.mydomain.net/api/v2/host/request/packages-status-update
                     *  https://repomanager.mydomain.net/api/v2/host/request/full-history-update
                     */
                    if (!empty($this->data->status) and ($this->action == 'packages-update' or $this->action == 'general-status-update' or $this->action == 'packages-status-update' or $this->action == 'full-history-update') and $this->method == 'PUT') {
                        try {
                            $this->hostController->acknowledgeRequest($this->action, $this->data->status);
                            return array('message' => 'Acknowledge has been taken into account.');
                        } catch (Exception $e) {
                            throw new Exception($e->getMessage());
                        }
                    }
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
