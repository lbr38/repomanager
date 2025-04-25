<?php

namespace Controllers\Api\Host;

use Exception;
use Datetime;

class Host extends \Controllers\Api\Controller
{
    private $component;
    private $action;

    public function execute()
    {
        $myhost = new \Controllers\Host();

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
            $myhost->setIp($this->data->ip);
        }
        if (!empty($this->data->hostname)) {
            $myhost->setHostname($this->data->hostname);
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
                        $list = $myhost->listAll();
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
             */
            if ($this->component == 'registering' and $this->method == 'POST') {
                /**
                 *  Try registering the host
                 */
                $myhost->register();

                /**
                 *  If register is successful, then return generated id and token
                 */
                return array('message' => array('Host successfully registered.'), 'results' => array('id' => $myhost->getAuthId(), 'token' => $myhost->getToken()));
            }

            /**
             *  Retrieve host database Id if its authId has been specified, it will be useful for next tasks
             */
            if (!empty($this->hostAuthId)) {
                try {
                    $myhost->setAuthId($this->hostAuthId);

                    // Get host database Id from its authId
                    $this->hostId = $myhost->getIdByAuth($this->hostAuthId);

                    // Set host database Id
                    $myhost->setId($this->hostId);

                    // Package controller will be useful for packages operations
                    $hostPackageController = new \Controllers\Host\Package\Package($this->hostId);
                } catch (Exception $e) {
                    throw new Exception('Coult not retrieve host Id in database');
                }
            }

            /**
             *  Following requests are only available if host authentication is valid and
             *  database Id has been retrieved
             */
            if ($this->hostAuthentication === true and !empty($myhost->getId())) {
                /**
                 *  Unregister a host
                 *  https://repomanager.mydomain.net/api/v2/host/registering
                 */
                if ($this->component == 'registering' and $this->method == 'DELETE') {
                    $myhost->delete($myhost->getId());
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
                            $myhost->updateHostname($this->data->hostname);
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
                            $myhost->updateOS($this->data->os);
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
                            $myhost->updateOsVersion($this->data->os_version);
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
                            $myhost->updateOsFamily($this->data->os_family);
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
                            $myhost->updateType($this->data->type);
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
                            $myhost->updateKernel($this->data->kernel);
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
                            $myhost->updateArch($this->data->arch);
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
                            $myhost->updateProfile($this->data->profile);
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
                            $myhost->updateEnv($this->data->env);
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
                            $myhost->updateAgentStatus($this->data->agent_status);
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
                            $myhost->updateLinupdateVersion($this->data->linupdate_version);
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
                            $myhost->updateRebootRequired($this->data->reboot_required);
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
                                $hostPackageController->setPackagesInventory($this->data->installed_packages);
                                return array('message' => array('Installed packages updated successfully.'));
                            } catch (Exception $e) {
                                throw new Exception('Installed packages update has failed: ' . $e->getMessage());
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
                                $hostPackageController->setPackagesAvailable($this->data->available_packages);
                                return array('message' => array('Available packages updated successfully.'));
                            } catch (Exception $e) {
                                throw new Exception('Available packages update has failed: ' . $e->getMessage());
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
                                $hostPackageController->setEventsFullHistory($this->data->events);
                                return array('message' => array('Package events history updated successfully.'));
                            } catch (Exception $e) {
                                throw new Exception('Package events history update has failed: ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
