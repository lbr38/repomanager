<?php

namespace Controllers\Api\V3\Environment;

use Controllers\Api\V3\Controller;
use Controllers\Api\V3\Response;

/**
 *  /environments: environments that can point to the snapshots of repositories
 */
class Environments extends Controller
{
    /**
     *  List the environments, a protected environment cannot be pointed nor removed
     */
    public function list(): Response
    {
        $environments = [];

        foreach ((new \Controllers\Environment())->listAll() as $env) {
            $environments[] = [
                'name'      => $env['Name'],
                'color'     => $env['Color'] ?? null,
                'protected' => ($env['Protected'] ?? 'false') == 'true'
            ];
        }

        return Response::ok($environments);
    }
}
