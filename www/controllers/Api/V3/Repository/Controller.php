<?php

namespace Controllers\Api\V3\Repository;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Repository\Type\Type;
use Controllers\Api\V3\Repository\Type\Repo;

/**
 *  Base class of the handlers of the /repositories/{type}/{id} routes
 */
abstract class Controller extends \Controllers\Api\V3\Controller
{
    /**
     *  Return the repository addressed by the route
     */
    protected function repository(): Type
    {
        return Type::load($this->request->param('type'), $this->request->id());
    }

    /**
     *  Return the curated or local repository addressed by the route (routes restricted to these types)
     */
    protected function repo(): Repo
    {
        $repository = $this->repository();

        if (!$repository instanceof Repo) {
            throw new HttpException(404, 'No matching route for ' . $repository->getLabel());
        }

        return $repository;
    }

    /**
     *  Return the snapshot Id specified in the route
     */
    protected function snapshotId(): int
    {
        return $this->request->id('snapshot');
    }
}
