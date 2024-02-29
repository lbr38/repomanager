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
     *  Retourne le liste des noms de repos actifs, par groupe
     *  Utilisée notamment pour les planifications de groupes
     */
    public function listNameByGroup(string $groupName)
    {
        return $this->model->listNameByGroup($groupName);
    }

    /**
     *  Retourne la liste des repos par groupes
     */
    public function listByGroup(string $groupName)
    {
        return $this->model->listByGroup($groupName);
    }

    /**
     *  Retourne un array de tous les noms de repos, sans informations des snapshots et environnements associés
     *  Si le paramètre 'true' est passé alors la fonction renverra uniquement les noms des repos qui ont un snapshot actif rattaché
     *  Si le paramètre 'false' est passé alors la fonction renverra tous les noms de repos avec ou sans snapshot rattaché
     */
    public function listNameOnly(bool $bool = false)
    {
        return $this->model->listNameOnly($bool);
    }

    /**
     *  Return the list of repos eligible for planifications (repos with at least 1 active snapshot)
     */
    public function listForPlan()
    {
        return $this->model->listForPlan();
    }
}
