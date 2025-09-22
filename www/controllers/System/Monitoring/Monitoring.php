<?php

namespace Controllers\System\Monitoring;

use Exception;

class Monitoring
{
    private $model;

    public function __construct()
    {
        $this->model = new \Models\System\Monitoring\Monitoring();
    }

    /**
     *  Get monitoring data between two timestamps
     */
    public function get(string $timestampStart, string $timestampEnd) : array
    {
        return $this->model->get($timestampStart, $timestampEnd);
    }

    /**
     *  Set monitoring data (%) in the database
     */
    public function set(float $cpuUsage, float $memoryUsage, float $diskUsage) : void
    {
        $this->model->set($cpuUsage, $memoryUsage, $diskUsage);
    }

    /**
     *  Clean old monitoring data (older than X days)
     */
    public function clean(int $days) : void
    {
        // Calculate the cutoff timestamp
        $timestamp = time() - ($days * 24 * 60 * 60);

        $this->model->clean($timestamp);
    }
}
