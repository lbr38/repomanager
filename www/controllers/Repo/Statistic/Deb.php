<?php

namespace Controllers\Repo\Statistic;

class Deb
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Statistic\Deb();
    }

    /**
     *  Return access request of the specified deb repository
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getAccess(string $name, string $dist, string $component, array $envs, int $timeStart, int $timeEnd, bool $count = false, bool $withOffset = false, int $offset = 0): array|int
    {
        return $this->model->getAccess($name, $dist, $component, $envs, $timeStart, $timeEnd, $count, $withOffset, $offset);
    }

    /**
     *  Return access request of the specified deb repository, for a given period
     */
    public function getAccessByPeriod(string $name, string $dist, string $component, array $envs, int $timeStart, int $timeEnd) : array|int
    {
        return $this->model->getAccessByPeriod($name, $dist, $component, $envs, $timeStart, $timeEnd);
    }

    /**
     *  Return the number of access requests to the specified repository, on a given date
     */
    public function getDailyAccessCount(string $name, string $dist, string $component, array $envs, int $timeStart, int $timeEnd): int
    {
        return $this->model->getDailyAccessCount($name, $dist, $component, $envs, $timeStart, $timeEnd);
    }

    /**
     *  Get the total number of access requests to the specified repository, on a given date, by IP
     *  It is possible to add an offset to the request
     */
    public function getAccessByIpCount(string $name, string $dist, string $component, array $envs, int $timeStart, int $timeEnd, bool $count = false, bool $withOffset = false, int $offset = 0): array|int
    {
        return $this->model->getAccessByIpCount($name, $dist, $component, $envs, $timeStart, $timeEnd, $count, $withOffset, $offset);
    }

    /**
     *  Add deb repository access log to database
     */
    public function addAccess(int $timestamp, string $name, string $dist, string $component, string $env, string $sourceHost, string $sourceIp, string $request, string $result): void
    {
        $this->model->addAccess($timestamp, $name, $dist, $component, $env, $sourceHost, $sourceIp, $request, $result);
    }
}
