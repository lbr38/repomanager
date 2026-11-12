<?php

namespace Controllers\Api\V3\Repository;

use Controllers\Api\V3\Response;
use Controllers\Api\V3\Schedule;

/**
 *  /repositories/{type}/{id}/snapshots/{snapshot}/environments
 */
class Environments extends Controller
{
    /**
     *  Point environment(s) to the snapshot: {"environments": ["preprod", "prod"]}
     */
    public function point(): Response
    {
        $repository = $this->repository();

        $this->request->allow(['environments', 'schedule']);

        $envs = $this->request->list('environments');
        $params = $repository->envParams($this->snapshotId(), $envs, Schedule::fromRequest($this->request));

        return $this->launch([$params], 'Pointing environment(s) ' . implode(', ', $envs) . ' to snapshot #' . $this->snapshotId() . ' of ' . $repository->getLabel());
    }

    /**
     *  Remove an environment from the snapshot
     */
    public function remove(): Response
    {
        $repository = $this->repository();
        $env = $this->request->param('env');

        $this->request->allow([]);

        return $this->launch([$repository->removeEnvParams($this->snapshotId(), $env)], 'Removing environment ' . $env . ' from snapshot #' . $this->snapshotId() . ' of ' . $repository->getLabel());
    }
}
