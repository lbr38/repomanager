<?php

namespace Controllers\Repo\Statistic;

class Rpm
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Statistic\Rpm();
    }

    /**
     *  Return access request of the specified rpm repository
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getAccess(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd, bool $count = false, bool $withOffset = false, int $offset = 0): array|int
    {
        return $this->model->getAccess($name, $releasever, $envs, $timeStart, $timeEnd, $count, $withOffset, $offset);
    }

    /**
     *  Return access request of the specified rpm repository, for a given period
     */
    public function getAccessByPeriod(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd): array|int
    {
        return $this->model->getAccessByPeriod($name, $releasever, $envs, $timeStart, $timeEnd);
    }

    /**
     *  Return the number of access requests to the specified repository, on a given date
     */
    public function getDailyAccessCount(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd): int
    {
        return $this->model->getDailyAccessCount($name, $releasever, $envs, $timeStart, $timeEnd);
    }

    /**
     *  Get the total number of access requests to the specified repository, on a given date, by IP
     *  It is possible to add an offset to the request
     */
    public function getAccessByIpCount(string $name, string $releasever, array $envs, int $timeStart, int $timeEnd, bool $count = false, bool $withOffset = false, int $offset = 0): array|int
    {
        return $this->model->getAccessByIpCount($name, $releasever, $envs, $timeStart, $timeEnd, $count, $withOffset, $offset);
    }

    /**
     *  Add rpm repository access log to database
     */
    public function addAccess(int $timestamp, string $name, string $releasever, string $env, string $sourceHost, string $sourceIp, string $request, string $result): void
    {
        $this->model->addAccess($timestamp, $name, $releasever, $env, $sourceHost, $sourceIp, $request, $result);
    }
}
