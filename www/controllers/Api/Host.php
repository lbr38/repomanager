<?php

namespace Controllers\Api;

use Exception;
use Datetime;

class Host
{
    private $hostController;

    public function __construct()
    {
        $this->hostController = new \Controllers\Host();
    }

    public function execute()
    {
        $component = '';
        $action = '';

        if (empty($_SERVER['REQUEST_URI']) or empty($_SERVER['REQUEST_METHOD'])) {
            throw new Exception('Error while parsing requested URL');
        }

        /**
         *  Parse URL
         */
        $url = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($url, PHP_URL_PATH);
        $uri = explode('/', $uri);
        if (isset($uri[4])) {
            $component = $uri[4];
        }
        if (isset($uri[5])) {
            $action = $uri[5];
        }

        /**
         *  Get sended data if any
         */
        $data = json_decode(file_get_contents("php://input"));

        if (!empty($data->id)) {
            $this->hostController->setAuthId($data->id);
        }
        if (!empty($data->token)) {
            $this->hostController->setToken($data->token);
        }
        if (!empty($data->ip)) {
            $this->hostController->setIp($data->ip);
        }
        if (!empty($data->hostname)) {
            $this->hostController->setHostname($data->hostname);
        }

        /**
         *  If a request is specified
         *  http://127.0.0.1/api/v2/host/
         */
        if (!empty($component)) {
            /**
             *  Register a new host
             *  http://127.0.0.1/api/v2/host/registering
             */
            if ($component == 'registering' and $method == 'POST') {
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
             *  From here host must be authenticated to execute following requests
             *  Check if id or token have been specified
             */
            if (empty($this->hostController->getAuthId()) or empty($this->hostController->getToken())) {
                Api::returnAuthenticationRequired();
            }

            /**
             *  Check if the id and token are valid
             */
            if (!Api::checkHostAuthentication($this->hostController->getAuthId(), $this->hostController->getToken())) {
                Api::returnBadCredentials();
            }

            /**
             *  Unregister a host
             *  http://127.0.0.1/api/v2/host/registering
             */
            if ($component == 'registering' and $method == 'DELETE') {
                $this->hostController->unregister();
                return array('message' => array('Host has been deleted.'));
            }

            /**
             *  Retrieving host database Id, it could be useful for next operations
             */
            try {
                $this->hostController->setId($this->hostController->getIdByAuth());
            } catch (Exception $e) {
                throw new Exception('Coult not retrieve host Id in database');
            }

            /**
             *  Host sends general informations and status
             *  http://127.0.0.1/api/v2/host/status
             */
            if ($component == 'status' and $method == 'PUT') {
                $message = array();

                /**
                 *  If hostname has been specified then update it in database
                 */
                if (!empty($data->hostname)) {
                    try {
                        $this->hostController->updateHostname($data->hostname);
                        $message[] = 'Hostname updated successfully.';
                    } catch (Exception $e) {
                        throw new Exception('Hostname update has failed.');
                    }
                }

                /**
                 *  If OS has been specified then update it in database
                 */
                if (!empty($data->os)) {
                    try {
                        $this->hostController->updateOS($data->os);
                        $message[] = 'OS updated successfully.';
                    } catch (Exception $e) {
                        throw new Exception('OS update has failed.');
                    }
                }

                /**
                 *  If OS release version has been specified then update it in database
                 */
                if (!empty($data->os_version)) {
                    try {
                        $this->hostController->updateOsVersion($data->os_version);
                        $message[] = 'OS version updated successfully.';
                    } catch (Exception $e) {
                        throw new Exception('OS version update has failed.');
                    }
                }

                /**
                 *  If OS family has been specified then update it in database
                 */
                if (!empty($data->os_family)) {
                    try {
                        $this->hostController->updateOsFamily($data->os_family);
                        $message[] = 'OS family updated successfully.';
                    } catch (Exception $e) {
                        throw new Exception('OS family update has failed.');
                    }
                }

                /**
                 *  If virtualization type has been specified then update it in database
                 */
                if (!empty($data->type)) {
                    try {
                        $this->hostController->updateType($data->type);
                        $message[] = 'Virtualization type updated successfully.';
                    } catch (Exception $e) {
                        throw new Exception('Virtualization type update has failed.');
                    }
                }

                /**
                 *  If kernel has been specified then update it in database
                 */
                if (!empty($data->kernel)) {
                    try {
                        $this->hostController->updateKernel($data->kernel);
                        $message[] = "Kernel updated successfully.";
                    } catch (Exception $e) {
                        throw new Exception('Kernel update has failed.');
                    }
                }

                /**
                 *  If architecture has been specified then update it in database
                 */
                if (!empty($data->arch)) {
                    try {
                        $this->hostController->updateArch($data->arch);
                        $message[] = "Arch updated successfully.";
                    } catch (Exception $e) {
                        throw new Exception('Arch update has failed.');
                    }
                }

                /**
                 *  If profile has been specified then update it in database
                 */
                if (!empty($data->profile)) {
                    try {
                        $this->hostController->updateProfile($data->profile);
                        $message[] = "Profile updated successfully.";
                    } catch (Exception $e) {
                        throw new Exception('Profile update has failed.');
                    }
                }

                /**
                 *  If environment has been specified then update it in database
                 */
                if (!empty($data->env)) {
                    try {
                        $this->hostController->updateEnv($data->env);
                        $message[] = "Environment updated successfully.";
                    } catch (Exception $e) {
                        throw new Exception('Environment update has failed.');
                    }
                }

                /**
                 *  If agent status has been specified then update it in database
                 */
                if (!empty($data->agent_status)) {
                    try {
                        $this->hostController->updateAgentStatus($data->agent_status);
                        $message[] = "Agent status updated successfully.";
                    } catch (Exception $e) {
                        throw new Exception('Agent status update has failed.');
                    }
                }

                /**
                 *  If linupdate version has been specified then update it in database
                 */
                if (!empty($data->linupdate_version)) {
                    try {
                        $this->hostController->updateLinupdateVersion($data->linupdate_version);
                        $message[] = "Linupdate version updated successfully.";
                    } catch (Exception $e) {
                        throw new Exception('Linupdate version update has failed.');
                    }
                }

                /**
                 *  If reboot required status has been specified then update it in database
                 */
                if (!empty($data->reboot_required)) {
                    try {
                        $this->hostController->updateRebootRequired($data->reboot_required);
                        $message[] = "Reboot status updated successfully.";
                    } catch (Exception $e) {
                        throw new Exception('Reboot status update has failed.');
                    }
                }

                return array('message' => $message);
            }

            /**
             *  Host send packages informations
             *  http://127.0.0.1/api/v2/host/packages
             */
            if ($component == 'packages' and $method == 'PUT') {
                /**
                 *  If installed packages list has been specified then update it in database
                 *  http://127.0.0.1/api/v2/host/packages/installed
                 */
                if ($action == 'installed' and $method == 'PUT') {
                    /**
                     *  If installed packages list has been specified then update it in database
                     */
                    if (!empty($data->installed_packages)) {
                        try {
                            $this->hostController->setPackagesInventory($data->installed_packages);
                            return array('message' => array('Installed packages updated successfully.'));
                        } catch (Exception $e) {
                            throw new Exception('Installed packages update has failed.');
                        }
                    }
                }

                /**
                 *  If available packages list has been specified then update it in database
                 *  http://127.0.0.1/api/v2/host/packages/available
                 */
                if ($action == 'available' and $method == 'PUT') {
                    /**
                     *  If available packages list has been specified then update it in database
                     */
                    if (!empty($data->available_packages)) {
                        try {
                            $this->hostController->setPackagesAvailable($data->available_packages);
                            return array('message' => array('Available packages updated successfully.'));
                        } catch (Exception $e) {
                            throw new Exception('Available packages update has failed.');
                        }
                    }
                }

                /**
                 *  If packages events list has been specified then update it in database
                 *  http://127.0.0.1/api/v2/host/packages/event
                 */
                if ($action == 'event' and $method == 'PUT') {
                    /**
                     *  If packages events history has been specified then update it in database
                     */
                    if (!empty($data->events)) {
                        try {
                            $this->hostController->setEventsFullHistory($data->events);
                            return array('message' => array('Package events history updated successfully.'));
                        } catch (Exception $e) {
                            throw new Exception('Package events history update has failed.');
                        }
                    }
                }
            }

            /**
             *  Host request acknowledgement
             *  http://127.0.0.1/api/v2/host/request
             */
            if ($component == 'request' and $method == 'PUT') {
                /**
                 *  http://127.0.0.1/api/v2/host/request/packages-update
                 */
                if (!empty($data->status) and ($action == 'packages-update' or $action == 'general-status-update' or $action == 'packages-status-update' or $action == 'full-history-update') and $method == 'PUT') {
                    try {
                        $this->hostController->setUpdateRequestStatus($action, $data->status);
                        return array('message' => 'Acknowledge has been taken into account.');
                    } catch (Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }
            }
        }

        throw new Exception('Invalid request');
    }
}
