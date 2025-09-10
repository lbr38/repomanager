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
     *  Return access request of the specified deb repository
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getDebAccess(string $name, string $dist, string $component, string $env, bool $count = false, bool $withOffset = false, int $offset = 0) : array|int
    {
        return $this->model->getDebAccess($name, $dist, $component, $env, $count, $withOffset, $offset);
    }

    /**
     *  Return access request of the specified rpm repository
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getRpmAccess(string $name, string $releasever, string $env, bool $count = false, bool $withOffset = false, int $offset = 0) : array|int
    {
        return $this->model->getRpmAccess($name, $releasever, $env, $count, $withOffset, $offset);
    }

    /**
     *  Count the number of access requests to the specified repo/section, on a given date
     */
    public function getDailyAccessCount(string $type, string $name, string|null $dist = null, string|null $section = null, string $env, string $date)
    {
        return $this->model->getDailyAccessCount($type, $name, $dist, $section, $env, $date);
    }

    /**
     *  Get the total number of access requests to the specified repo/section, on a given date, by IP
     *  It is possible to add an offset to the request
     */
    public function getAccessIpCount(string $type, string $name, string|null $dist = null, string|null $section = null, string $env, string $date, bool $withOffset = false, int $offset = 0)
    {
        return $this->model->getAccessIpCount($type, $name, $dist, $section, $env, $date, $withOffset, $offset);
    }

    /**
     *  Ajoute de nouvelles statistiques à la table stats
     */
    public function add(string $date, string $time, string $repoSize, string $packagesCount, string $envId)
    {
        $this->model->add($date, $time, $repoSize, $packagesCount, $envId);
    }

    /**
     *  Add deb repository access log to database
     */
    public function addDebAccess(string $date, string $time, string $name, string $dist, string $component, string $env, string $sourceHost, string $sourceIp, string $request, string $result) : void
    {
        $this->model->addDebAccess($date, $time, $name, $dist, $component, $env, $sourceHost, $sourceIp, $request, $result);
    }

    /**
     *  Add rpm repository access log to database
     */
    public function addRpmAccess(string $date, string $time, string $name, string $releasever, string $env, string $sourceHost, string $sourceIp, string $request, string $result) : void
    {
        $this->model->addRpmAccess($date, $time, $name, $releasever, $env, $sourceHost, $sourceIp, $request, $result);
    }

    /**
     *  Add new repo access log to queue
     */
    public function addAccessToQueue(string $request)
    {
        $this->model->addAccessToQueue($request);
    }

    /**
     *  Return access queue
     */
    public function getAccessQueue()
    {
        return $this->model->getAccessQueue();
    }

    /**
     *  Delete access log from queue
     */
    public function deleteFromQueue(string $id)
    {
        $this->model->deleteFromQueue($id);
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
}
