<?php

namespace Controllers\Service\Unit;

use \Controllers\App\Maintenance;
use Exception;

class Database extends \Controllers\Service\Service
{
    private $monitoringController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->monitoringController = new \Controllers\System\Monitoring\Monitoring();
    }

    /**
     *  Perform a VACUUM/ANALYZE and integrity check on the databases
     */
    public function maintenance() : void
    {
        // TODO : add all hosts databases with a glob()
        $databases = ['main', 'stats', 'hosts', 'ws'];

        // Enable maintenance page, this will avoid any write operation during the maintenance
        parent::log('Enabling maintenance page');
        Maintenance::set('on');

        foreach ($databases as $database) {
            try {
                parent::log('Starting maintenance on database ' . $database);
                $databaseMaintenanceController = new \Controllers\Database\Maintenance($database);

                parent::log('Performing VACUUM on database ' . $database);
                $databaseMaintenanceController->vacuum();

                parent::log('Performing ANALYZE on database ' . $database);
                $databaseMaintenanceController->analyze();

                parent::log('Performing integrity check on database ' . $database);
                $databaseMaintenanceController->integrityCheck();

                unset($databaseMaintenanceController);

                parent::log('Maintenance completed successfully on database ' . $database);
            } catch (Exception $e) {
                parent::logError('Error during database ' . $database . ' maintenance: ' . $e->getMessage());
            }
        }

        // Disable maintenance page
        parent::log('Disabling maintenance page');
        Maintenance::set('off');
    }
}
