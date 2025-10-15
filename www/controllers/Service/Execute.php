<?php

namespace Controllers\Service;

use Exception;
use Error;

class Execute extends Service
{
    public function __construct(string $unit)
    {
        parent::__construct($unit);

        // Default controller and method to call
        $controller = '\Controllers\Service\Unit\Main';
        $method = 'run';

        // Load service units configuration
        include(ROOT . '/config/service-units.php');

        try {
            // If a specific unit is called, define controller and method to call
            if ($unit != 'main') {
                if (!in_array($unit, array_keys($units))) {
                    throw new Exception('Service unit "' . $unit . '" is not defined');
                }

                $controller = '\Controllers\\' . $units[$unit]['controller'];
                $method = $units[$unit]['method'];
            }

            // Set process title
            cli_set_process_title('repomanager.' . $unit);

            // Instantiate the controller
            try {
                $serviceController = new $controller($unit);
            } catch (Exception | Error $e) {
                throw new Exception('Could not instantiate controller ' . $controller . ': ' . $e->getMessage());
            }

            // Call the method
            try {
                $serviceController->$method();
            } catch (Exception | Error $e) {
                throw new Exception($e->getMessage());
            }
        } catch (Exception $e) {
            $this->logError($e->getMessage());
            exit(1);
        }

        exit(0);
    }
}
