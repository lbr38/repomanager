<?php

namespace Controllers\Mirror;

class Statistic
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Mirror\Statistic();
    }

    /**
     *  Return access requests of the specified mirror, most recent first
     *  It is possible to count the number of requests
     *  It is possible to add an offset to the request
     */
    public function getAccess(int $mirrorId, array $envs, int $timeStart, int $timeEnd, bool $count = false, bool $withOffset = false, int $offset = 0): array|int
    {
        return $this->model->getAccess($mirrorId, $envs, $timeStart, $timeEnd, $count, $withOffset, $offset);
    }

    /**
     *  Return the number of access requests of the specified mirror, by environment and timestamp, for a given period
     */
    public function getAccessByPeriod(int $mirrorId, array $envs, int $timeStart, int $timeEnd): array
    {
        return $this->model->getAccessByPeriod($mirrorId, $envs, $timeStart, $timeEnd);
    }

    /**
     *  Return the number of access requests of the specified mirror, for a given period
     */
    public function getAccessCount(int $mirrorId, int $timeStart, int $timeEnd): int
    {
        return $this->model->getAccess($mirrorId, [], $timeStart, $timeEnd, true, false, 0);
    }

    /**
     *  Return the number of access requests of the specified mirror, grouped by IP
     *  It is possible to count the number of unique IPs
     *  It is possible to add an offset to the request
     */
    public function getAccessByIpCount(int $mirrorId, array $envs, int $timeStart, int $timeEnd, bool $count = false, bool $withOffset = false, int $offset = 0): array|int
    {
        return $this->model->getAccessByIpCount($mirrorId, $envs, $timeStart, $timeEnd, $count, $withOffset, $offset);
    }

    /**
     *  Add a mirror access request
     */
    public function addAccess(int $timestamp, int $mirrorId, string $env, string $sourceHost, string $sourceIp, string $request, string $result): void
    {
        $this->model->addAccess($timestamp, $mirrorId, $env, $sourceHost, $sourceIp, $request, $result);
    }
}
