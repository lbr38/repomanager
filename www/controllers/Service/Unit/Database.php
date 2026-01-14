<?php

namespace Controllers\Service\Unit;

use \Controllers\App\Maintenance;
use Exception;

class Database extends \Controllers\Service\Service
{
    private $monitoringController;
    private $hostController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->monitoringController = new \Controllers\System\Monitoring\Monitoring();
        $this->hostController = new \Controllers\Host();
    }

    /**
     *  Perform a VACUUM/ANALYZE and integrity check on the databases
     */
    public function maintenance() : void
    {
        $databases = ['main', 'stats', 'hosts', 'ws'];

        // Get all hosts
        $hosts = $this->hostController->listAll();

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

        // TODO
        // foreach ($hosts as $host) {
        //     try {
        //         // Get host Id
        //         $hostId = $host['Id'];

        //         // Get hostname
        //         $hostname = $host['Hostname'];

        //         parent::log('Starting maintenance on host #' . $hostId . ' (' . $hostname . ') database');

        //         // Check if host has a dedicated database
        //         if (!file_exists(DATA_DIR . '/hosts/' . $hostId . '/properties.db')) {
        //             parent::log('Host #' . $hostId . ' does not have a dedicated database, skipping...');
        //             continue;
        //         }

        //         parent::log('Maintenance completed successfully on host #' . $hostId . ' (' . $hostname . ') database');
        //     } catch (Exception $e) {
        //         parent::logError('Error during host #' . $hostId . ' (' . $hostname . ') database maintenance: ' . $e->getMessage());
        //     }
        // }

        // Disable maintenance page
        parent::log('Disabling maintenance page');
        Maintenance::set('off');
    }
}
