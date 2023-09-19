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
     *  Return repo snapshot size (by its env Id) for the last 30 days (default)
     */
    public function getEnvSize(string $envId, int $days = 30)
    {
        return $this->model->getEnvSize($envId, $days);
    }

    /**
     *  Return repo snapshot packages count (by its env Id) for the last 30 days (default)
     */
    public function getPkgCount(string $envId, int $days = 30)
    {
        return $this->model->getPkgCount($envId, $days);
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
     *  Add new repo access log to database
     */
    public function addAccess(string $date, string $time, string $sourceHost, string $sourceIp, string $request, string $result)
    {
        $this->model->addAccess($date, $time, $sourceHost, $sourceIp, $request, $result);
    }

    /**
     *  Clean oldest repos statistics by deleting rows in database
     */
    public function clean(string $period = '366 days')
    {
        /**
         *  Time period starts from the very beginning of repomanager existence
         *  And ends $period days ago before the current day
         */
        $dateStart = '2020-01-01';
        $dateEnd = date('Y-m-d', strtotime('-' . $period, strtotime(DATE_YMD)));

        $this->model->clean($dateStart, $dateEnd);
    }

    /**
     *  Fermeture de la connexion à la base de données
     */
    public function closeConnection()
    {
        $this->model->closeConnection();
    }
}
