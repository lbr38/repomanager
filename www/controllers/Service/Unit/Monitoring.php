<?php

namespace Controllers\Service\Unit;

use Exception;
use Controllers\System\Monitoring\Cpu as Cpu;
use Controllers\System\Monitoring\Memory as Memory;
use Controllers\System\Monitoring\Disk as Disk;

class Monitoring extends \Controllers\Service\Service
{
    private $monitoringController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->monitoringController = new \Controllers\System\Monitoring\Monitoring();
    }

    /**
     *  Monitor CPU, memory and disk usage and log it
     */
    public function monitor() : void
    {
        parent::log('Logging system usage');

        $cpuUsage    = Cpu::getUsage();
        $memoryUsage = Memory::getUsage();
        $diskUsage   = Disk::getUsage(REPOS_DIR);

        // Add to database
        $this->monitoringController->set($cpuUsage, $memoryUsage, $diskUsage);

        // Delete old monitoring data (older than 30 days)
        $this->monitoringController->clean(30);
    }
}
