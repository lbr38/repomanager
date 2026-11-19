<?php

namespace Controllers\Api\V3\Host;

use Exception;
use Controllers\Api\HttpException;
use Controllers\Api\V3\Controller;
use Controllers\Api\V3\Response;
use Controllers\Host\Host as HostController;
use Controllers\User\Permission\Host as HostPermission;

/**
 *  Routes used by the linupdate agent installed on hosts
 *  A host registers with the API key of a user, then authenticates with the Id and token returned: /hosts/me is the authenticated host
 */
class Agent extends Controller
{
    /**
     *  Register a new host: {"ip": "...", "hostname": "..."}
     *  Return the Id and token the host must use to authenticate
     */
    public function register(): Response
    {
        if (!HostPermission::allowedAction('register')) {
            throw new HttpException(403, 'You are not allowed to register a host');
        }

        $this->request->allow(['ip', 'hostname']);

        $result = (new \Controllers\Host\Registering())->register($this->request->string('ip'), $this->request->string('hostname'));
        $hostId = (new HostController())->getIdByAuth($result['authId']);

        // The Id and token to authenticate with, the host database Id is in the Location header
        return Response::created([
            'id'    => $result['authId'],
            'token' => $result['token']
        ], \Controllers\Api\V3\Request::ROOT . '/hosts/' . $hostId, 'Host successfully registered.');
    }

    /**
     *  Unregister the authenticated host
     */
    public function unregister(): Response
    {
        (new HostController())->deleteById($this->hostId());

        return Response::message('Host has been deleted.');
    }

    /**
     *  Update the general information and status of the authenticated host
     *  Accepted values: hostname, os, os_version, os_family, type, cpu, ram, network, kernel, arch, profile, env,
     *  agent_status, agent_version, reboot_required, uptime, public_ip, behind_nat
     */
    public function status(): Response
    {
        $updated = (new \Controllers\Host\Update())->updateStatus($this->hostId(), $this->request->body());

        return Response::ok(['updated' => array_keys($updated)], 'Host status updated');
    }

    /**
     *  Update the installed packages ({"installed_packages": [...]}) or the available updates ({"available_packages": [...]})
     *  of the authenticated host
     */
    public function packages(): Response
    {
        $hostPackageController = new \Controllers\Host\Package\Package($this->hostId());

        if ($this->request->param('state') == 'installed') {
            $this->request->allow(['installed_packages']);

            try {
                $hostPackageController->setPackagesInventory($this->request->array('installed_packages'));
            } catch (Exception $e) {
                throw HttpException::invalidParameter('Installed packages update has failed: ' . $e->getMessage(), '#/installed_packages');
            }

            return Response::message('Installed packages updated successfully.');
        }

        $this->request->allow(['available_packages']);

        try {
            $hostPackageController->setPackagesAvailable($this->request->array('available_packages'));
        } catch (Exception $e) {
            throw HttpException::invalidParameter('Available packages update has failed: ' . $e->getMessage(), '#/available_packages');
        }

        return Response::message('Available packages updated successfully.');
    }

    /**
     *  Update the packages events history of the authenticated host: {"events": [...]}
     */
    public function events(): Response
    {
        $this->request->allow(['events']);

        try {
            (new \Controllers\Host\Package\Event($this->hostId()))->setHistory($this->request->array('events'));
        } catch (Exception $e) {
            throw HttpException::invalidParameter('Package events history update has failed: ' . $e->getMessage(), '#/events');
        }

        return Response::message('Package events history updated successfully.');
    }

    /**
     *  Return the database Id of the authenticated host
     */
    private function hostId(): int
    {
        $id = (new HostController())->getIdByAuth(HOST_AUTH_ID);

        if (empty($id)) {
            throw new HttpException(401, 'No host found with this authentication Id');
        }

        return $id;
    }
}
