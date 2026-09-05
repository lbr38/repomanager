<?php

namespace Controllers\Repo;

use Exception;

class Listing
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Listing();
    }

    /**
     *  Return the list of repos, their snapshots and their environments
     */
    public function list() : array
    {
        return $this->model->list();
    }

    /**
     *  Return the list of repos by group name
     */
    public function listByGroup(string $groupName)
    {
        return $this->model->listByGroup($groupName);
    }

    /**
     *  Return an array of all repo names, with or without associated snapshots and environments
     *  If 'true' parameter is passed then the function will return only the names of the repos that have an active snapshot attached
     *  If 'false' parameter is passed then the function will return all repo names with or without attached snapshot
     */
    public function listNameOnly(bool $withActiveSnapshots = false)
    {
        return $this->model->listNameOnly($withActiveSnapshots);
    }

    /**
     *  Return the list of snapshots for a repository
     */
    public function listSnapshots(int $repoId) : array
    {
        return $this->model->listSnapshots($repoId);
    }

    /**
     *  Return the list of repositories matching the specified filters, with their latest active snapshot Id
     */
    public function listByTarget(string $groupId = '', array $tags = [], string $packageType = '') : array
    {
        return $this->model->listByTarget($groupId, $tags, $packageType);
    }

    /**
     *  Return the list of all distinct tags used by repositories
     */
    public function listTags(): array
    {
        return $this->model->listTags();
    }
}
