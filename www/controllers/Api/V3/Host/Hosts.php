<?php

namespace Controllers\Api\V3\Host;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Controller;
use Controllers\Api\V3\Response;
use Controllers\Host\Host as HostController;
use Controllers\Host\Listing;
use Controllers\Host\Package\Package as HostPackage;
use Controllers\User\Permission\Host as HostPermission;

/**
 *  /hosts
 *  /hosts/{id}
 */
class Hosts extends Controller
{
    // Filters on the hosts properties: query parameter => host column
    private const FILTERS = [
        'os'         => 'Os',
        'os_version' => 'Os_version',
        'os_family'  => 'Os_family',
        'kernel'     => 'Kernel',
        'arch'       => 'Arch',
        'type'       => 'Type',
        'profile'    => 'Profile',
        'env'        => 'Env'
    ];

    // Hosts statuses that can be filtered
    private const STATUSES = ['uptodate', 'outdated', 'compliant', 'non-compliant'];

    /**
     *  List hosts, with optional filters that can be combined:
     *  - properties: ?os=debian&os_version=12&kernel=...&arch=...&type=...&profile=...&env=...
     *  - status: ?status=uptodate|outdated|compliant|non-compliant (&packages to include the available updates)
     *  - package: ?package=<name>&version=<version>&strict_name&strict_version&absent
     */
    public function list(): Response
    {
        $listingController = new Listing();

        if (!HostPermission::allowed()) {
            throw new HttpException(403, 'You are not allowed to list hosts');
        }

        $hosts = [];
        // Additional data of the hosts matching a status or a package filter, as an array host Id => data
        $matches = null;

        $status = $this->request->query('status');
        $package = $this->request->query('package');

        if ($status !== null) {
            $matches = $this->matchStatus($status);
        }

        if ($package !== null) {
            $packageMatches = $this->matchPackage($package);

            // Hosts must match both filters, their data are merged
            if ($matches === null) {
                $matches = $packageMatches;
            } else {
                foreach ($matches as $id => $data) {
                    if (!isset($packageMatches[$id])) {
                        unset($matches[$id]);
                        continue;
                    }

                    $matches[$id] = array_merge($data, $packageMatches[$id]);
                }
            }
        }

        foreach ($listingController->get() as $host) {
            if ($matches !== null and !isset($matches[$host['Id']])) {
                continue;
            }

            if (!$this->matchProperties($host)) {
                continue;
            }

            $hosts[] = array_merge(self::format($host), $matches[$host['Id']] ?? []);
        }

        return Response::page($this->request, $hosts);
    }

    /**
     *  Return a host details
     */
    public function get(): Response
    {
        $id = $this->request->id();

        self::checkAccess($id);

        return Response::ok(self::format((new HostController())->get($id)));
    }

    /**
     *  Delete a host
     */
    public function delete(): Response
    {
        $hostController = new HostController();
        $id = $this->request->id();

        if (!HostPermission::allowedAction('delete')) {
            throw new HttpException(403, 'You are not allowed to delete a host');
        }

        if (!$hostController->existsId($id)) {
            throw new HttpException(404, 'Host #' . $id . ' not found');
        }

        $hostController->deleteById($id);

        return Response::message('Host #' . $id . ' has been deleted');
    }

    /**
     *  Return the installed packages or the available updates of a host
     */
    public function packages(): Response
    {
        $id = $this->request->id();

        self::checkAccess($id);

        $hostPackageController = new HostPackage($id);

        if ($this->request->param('state') == 'installed') {
            return Response::page($this->request, $hostPackageController->getInstalled());
        }

        return Response::page($this->request, $hostPackageController->getAvailable());
    }

    /**
     *  Check that the host exists and that the request is allowed to access its data:
     *  a host can only access its own data, a user must have permissions on hosts
     */
    private static function checkAccess(int $id): void
    {
        $hostController = new HostController();

        if (defined('HOST_AUTH_ID')) {
            if ($hostController->getIdByAuth(HOST_AUTH_ID) != $id) {
                throw new HttpException(403, 'A host can only access its own data');
            }

            return;
        }

        if (!HostPermission::allowed()) {
            throw new HttpException(403, 'You are not allowed to access hosts');
        }

        if (!$hostController->existsId($id)) {
            throw new HttpException(404, 'Host #' . $id . ' not found');
        }
    }

    /**
     *  Return true if the host matches the properties filters of the request
     */
    private function matchProperties(array $host): bool
    {
        foreach (self::FILTERS as $parameter => $column) {
            $value = $this->request->query($parameter);

            if ($value !== null and (string) ($host[$column] ?? '') !== $value) {
                return false;
            }
        }

        return true;
    }

    /**
     *  Return the hosts matching a status, as an array host Id => status data (available updates, compliance)
     */
    private function matchStatus(string $status): array
    {
        $listingController = new Listing();
        $matches = [];
        $listPackages = $this->request->queryFlag('packages');

        $hosts = match ($status) {
            'uptodate'      => $listingController->getUpToDate($listPackages),
            'outdated'      => $listingController->getOutdated($listPackages),
            'compliant'     => $listingController->getCompliant($listPackages),
            'non-compliant' => $listingController->getNonCompliant($listPackages),
            default         => throw HttpException::invalidQueryParameter('Invalid status ' . $status . ' (' . implode(', ', self::STATUSES) . ')', 'status')
        };

        foreach ($hosts as $host) {
            // Keep the data that are not host properties
            $matches[$host['Id']] = array_diff_key($host, array_flip(['Id', 'Hostname', 'Ip', 'Public_ip', 'Os', 'Os_version', 'Os_family', 'Kernel', 'Arch', 'Type', 'Profile', 'Env']));
        }

        return $matches;
    }

    /**
     *  Return the hosts on which a package is installed (or absent), as an array host Id => matching packages
     */
    private function matchPackage(string $package): array
    {
        $matches = [];

        // Both strict filters can be set at once with 'strict'
        $strict = $this->request->queryFlag('strict');
        $absent = $this->request->queryFlag('absent');

        $hosts = (new Listing())->getByPackage(
            $package,
            $this->request->query('version', ''),
            $strict or $this->request->queryFlag('strict_name'),
            $strict or $this->request->queryFlag('strict_version'),
            $absent
        );

        foreach ($hosts as $host) {
            $matches[$host['id']] = $absent ? [] : ['packages' => $host['packages']];
        }

        return $matches;
    }

    /**
     *  Return a host details, without its authentication Id and token (they allow to authenticate as the host)
     */
    public static function format(array $host): array
    {
        unset($host['AuthId'], $host['Token']);

        $host['Id'] = (int) $host['Id'];

        return $host;
    }
}
