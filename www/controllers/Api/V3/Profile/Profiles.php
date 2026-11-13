<?php

namespace Controllers\Api\V3\Profile;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Controller;
use Controllers\Api\V3\Response;

/**
 *  /profiles: hosts profiles, the configuration retrieved by the linupdate agent
 */
class Profiles extends Controller
{
    /**
     *  List the profiles names
     */
    public function list(): Response
    {
        return Response::ok(array_values((new \Controllers\Profile())->listName()));
    }

    /**
     *  Return the configuration of a profile (packages exclusions, services to reload or restart...)
     */
    public function get(): Response
    {
        $name = $this->profile();

        return Response::ok((new \Controllers\Profile())->getProfileConfiguration($name));
    }

    /**
     *  Return the repositories of a profile, as configured on hosts by the linupdate agent
     */
    public function repositories(): Response
    {
        $name = $this->profile();

        return Response::ok((new \Controllers\Profile())->getReposMembersList($name, true));
    }

    /**
     *  Return the profile name specified in the route, throw a 404 error if it does not exist
     */
    private function profile(): string
    {
        $name = $this->request->param('name');

        if (!(new \Controllers\Profile())->exists($name)) {
            throw new HttpException(404, 'Profile ' . $name . ' not found');
        }

        return $name;
    }
}
