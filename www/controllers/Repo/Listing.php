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
     *  Retourne la liste des repos, leurs snapshots et leur environnements
     *  N'affiche pas les repos qui n'ont aucun environnement actif
     */
    public function list()
    {
        return $this->model->list();
    }

    /**
     *  Retourne la liste des repos par groupes
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
}
