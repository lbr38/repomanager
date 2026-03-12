<?php

namespace Controllers\Repo\Statistic;

class Statistic
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\Repo\Statistic\Statistic();
    }

    /**
     *  Return the statistics for a given repository ID
     */
    public function getByRepoId(int $repoId): array
    {
        return $this->model->getByRepoId($repoId);
    }

    /**
     *  Return access queue
     */
    public function getAccessQueue(): array
    {
        return $this->model->getAccessQueue();
    }

    /**
     *  Add statistics to database
     */
    public function add(int $timestamp, string $snapshotDate, int $snapshotSize, int $snapshotPackagesCount, int $repoId): void
    {
        $this->model->add($timestamp, $snapshotDate, $snapshotSize, $snapshotPackagesCount, $repoId);
    }

    /**
     *  Add new repository access log to queue
     */
    public function addAccessToQueue(string $request): void
    {
        $this->model->addAccessToQueue($request);
    }

    /**
     *  Clean stats older than the specified number of days
     */
    public function clean(int $days): void
    {
        $this->model->clean(strtotime('-' . $days . ' days'));
    }

    /**
     *  Delete access log from queue
     */
    public function deleteFromQueue(int $id): void
    {
        $this->model->deleteFromQueue($id);
    }
}
