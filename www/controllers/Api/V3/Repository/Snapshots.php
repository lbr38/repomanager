<?php

namespace Controllers\Api\V3\Repository;

use Controllers\Api\HttpException;
use Controllers\Api\V3\Response;
use Controllers\Api\V3\Schedule;
use Controllers\Api\V3\Repository\Type\Type;

/**
 *  /repositories/{type}/{id}/snapshots
 *  /repositories/{type}/{id}/snapshots/{snapshot}
 */
class Snapshots extends Controller
{
    /**
     *  List the active snapshots of the repository, most recent first
     */
    public function list(): Response
    {
        return Response::ok($this->repository()->formatSnapshots());
    }

    /**
     *  Create a new snapshot: sync from the source repository (curated repositories and mirrors),
     *  or copy of the latest snapshot to add packages to (local repositories)
     */
    public function create(): Response
    {
        $repository = $this->repository();
        $params = $repository->newSnapshotParams($this->request, Schedule::fromRequest($this->request));

        return $this->launch([$params], ucfirst($repository->getType() == 'local' ? 'new snapshot creation' : 'sync') . ' of ' . $repository->getLabel());
    }

    /**
     *  Return the snapshot details
     */
    public function get(): Response
    {
        return Response::ok($this->repository()->formatSnapshot($this->snapshotId()));
    }

    /**
     *  Delete the snapshot (a curated or local repository is deleted with its last snapshot)
     */
    public function delete(): Response
    {
        $repository = $this->repository();

        $this->request->allow(['schedule']);

        return $this->launch([$repository->deleteSnapshotParams($this->snapshotId(), Schedule::fromRequest($this->request))], 'Deletion of snapshot #' . $this->snapshotId() . ' of ' . $repository->getLabel());
    }

    /**
     *  Rebuild the metadata of the snapshot (curated and local repositories)
     */
    public function rebuild(): Response
    {
        $repository = $this->repo();

        return $this->launch([$repository->rebuildParams($this->snapshotId(), $this->request, Schedule::fromRequest($this->request))], 'Metadata rebuild of snapshot #' . $this->snapshotId() . ' of ' . $repository->getLabel());
    }

    /**
     *  Duplicate the snapshot into a new repository (curated and local repositories)
     */
    public function duplicate(): Response
    {
        $repository = $this->repo();

        return $this->launch([$repository->duplicateParams($this->snapshotId(), $this->request, Schedule::fromRequest($this->request))], 'Duplication of snapshot #' . $this->snapshotId() . ' of ' . $repository->getLabel());
    }

    /**
     *  Return the packages added and removed between the snapshot and another snapshot
     *  The other snapshot is a snapshot of the same repository, or of the repository specified by ?other_repository=<type>/<id>
     */
    public function diff(): Response
    {
        $repository = $this->repo();
        $otherRepository = $repository;
        $snapId = $this->snapshotId();
        $otherSnapId = $this->request->id('other');
        $otherReference = $this->request->query('other_repository');

        if ($otherReference !== null) {
            if (!preg_match('#^(curated|local)/([0-9]+)$#', $otherReference, $matches)) {
                throw HttpException::invalidQueryParameter('Parameter other_repository must be the type and Id of a curated or local repository, e.g. local/3', 'other_repository');
            }

            $otherRepository = Type::load($matches[1], (int) $matches[2]);
        }

        // Both snapshots must be active snapshots of their repository
        $repository->formatSnapshot($snapId);
        $otherRepository->formatSnapshot($otherSnapId);

        $diff = (new \Controllers\Repo\Snapshot\Snapshot())->diff($snapId, $otherSnapId);

        return Response::ok([
            'added'   => Packages::formatList($diff['added']),
            'removed' => Packages::formatList($diff['removed'])
        ]);
    }
}
