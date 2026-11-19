<?php

namespace Controllers\Api\V3\Repository;

use Controllers\Api\V3\Response;
use Controllers\Api\V3\Schedule;
use Controllers\Api\V3\Repository\Type\Type;
use Controllers\Api\V3\Repository\Type\Repo;
use Controllers\Api\V3\Repository\Type\Mirror;

/**
 *  /repositories
 *  /repositories/{type}
 *  /repositories/{type}/{id}
 */
class Repositories extends Controller
{
    /**
     *  List the repositories the user is allowed to view, of all types or of the type specified in the route
     *  Optional filters: ?package_type=deb&tag=<tag>&group=<group name>
     */
    public function list(): Response
    {
        $type = $this->request->optionalParam('type');
        $repositories = [];

        if ($type === null or $type != 'mirror') {
            $repositories = Repo::list($type);
        }

        if ($type === null or $type == 'mirror') {
            $repositories = array_merge($repositories, Mirror::list());
        }

        $packageType = $this->request->query('package_type');
        $tag = $this->request->query('tag');
        $group = $this->request->query('group');

        $repositories = array_values(array_filter($repositories, function ($repository) use ($packageType, $tag, $group) {
            if ($packageType !== null and $repository['package_type'] != $packageType) {
                return false;
            }

            if ($tag !== null and !in_array($tag, $repository['tags'], true)) {
                return false;
            }

            if ($group !== null and !in_array($group, $repository['groups'], true)) {
                return false;
            }

            return true;
        }));

        return Response::ok($repositories);
    }

    /**
     *  Create a new repository: the creation is a task, executed in background
     */
    public function create(): Response
    {
        $type = $this->request->param('type');
        $schedule = Schedule::fromRequest($this->request);

        if ($type == 'mirror') {
            return $this->launch([Mirror::createParams($this->request, $schedule)], 'Mirror creation');
        }

        return $this->launch([Repo::createParams($type, $this->request, $schedule)], ucfirst($type) . ' repository creation');
    }

    /**
     *  Return the repository details, with its snapshots
     */
    public function get(): Response
    {
        return Response::ok($this->repository()->format());
    }

    /**
     *  Edit the repository properties (description, tags, retention, source of a curated repository)
     */
    public function edit(): Response
    {
        $repository = $this->repository();
        $repository->edit($this->request);

        return Response::ok(Type::load($repository->getType(), $repository->getId())->format(), ucfirst($repository->getLabel()) . ' has been edited');
    }

    /**
     *  Delete the repository with all its snapshots
     */
    public function delete(): Response
    {
        $repository = $this->repository();

        $this->request->allow(['schedule']);

        return $this->launch($repository->deleteParams(Schedule::fromRequest($this->request)), ucfirst($repository->getLabel()) . ' deletion');
    }

    /**
     *  Rename a curated or local repository
     */
    public function rename(): Response
    {
        $repository = $this->repo();

        return $this->launch([$repository->renameParams($this->request, Schedule::fromRequest($this->request))], ucfirst($repository->getLabel()) . ' renaming');
    }
}
