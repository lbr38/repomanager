<?php

namespace Controllers;

use Exception;

class Stat
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Stat();
    }

    /**
     *  Retourne tout le contenu de la table stats
     */
    public function getAll(string $envId)
    {
        return $this->model->getAll($envId);
    }

    /**
     *  Retourne le détails des 50 dernières requêtes du repo/section spécifié
     */
    public function getLastAccess(string $name, string $dist = null, string $section = null, string $env)
    {
        return $this->model->getLastAccess($name, $dist, $section, $env);
    }

    /**
     *  Retourne le détail des requêtes sur le repo/section spécifié, des 5 dernières minutes
     */
    public function getLastMinutesAccess(string $name, string $dist = null, string $section = null, string $env)
    {
        return $this->model->getLastMinutesAccess($name, $dist, $section, $env);
    }

    /**
     *  Retourne le détail des requêtes en temps réel (date et heure actuelles +/- 5sec) sur le repo/section spécifié
     */
    public function getRealTimeAccess(string $name, string $dist = null, string $section = null, string $env)
    {
        return $this->model->getRealTimeAccess($name, $dist, $section, $env);
    }

    /**
     *  Compte le nombre de requêtes d'accès au repo/section spécifié, sur une date donnée
     */
    public function getDailyAccessCount(string $name, string $dist = null, string $section = null, string $env, string $date)
    {
        return $this->model->getDailyAccessCount($name, $dist, $section, $env, $date);
    }

    /**
     *  Ajoute de nouvelles statistiques à la table stats
     */
    public function add(string $date, string $time, string $repoSize, string $packagesCount, string $envId)
    {
        $this->model->add($date, $time, $repoSize, $packagesCount, $envId);
    }

    /**
     *  Fermeture de la connexion à la base de données
     */
    public function closeConnection()
    {
        $this->model->closeConnection();
    }
}
