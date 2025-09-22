<?php

namespace Controllers\Service\Unit;

use Exception;

class Main extends \Controllers\Service\Service
{
    private $taskController;

    public function __construct(string $unit)
    {
        parent::__construct($unit);

        $this->taskController = new \Controllers\Task\Task();
        $this->signalHandler = new \Controllers\SignalHandler();
    }

    /**
     *  Run main service
     */
    public function run() : void
    {
        $counter = 0;
        $lastScheduledTaskRunning = null;
        $lastStatsRunning = null;
        $startup = true;

        // Load service units configuration
        include(ROOT . '/config/service-units.php');

        /**
         *  As this service is the main service, if it stops, all other services must stop too
         *  Define stop files to create on interrupt, those files are used to make unit services stop on interrupt
         */
        $this->signalHandler->touchFileOnInterrupt([
            DATA_DIR . '/.service.stats-parse.stop',
            DATA_DIR . '/.service.stats-process.stop',
        ]);

        // Main loop, every minute
        while (true) {
            $launchedUnits = [];
            $currentTime   = date('H:i');
            $minutesNow    = date('i');

            /**
             *  If current minute is 0 (beginning of the hour) or if we are at startup, run hourly tasks
             */
            if ($minutesNow == '00' || $startup === true) {
                // Run hourly unit tasks
                foreach ($units as $unitName => $unit) {
                    if (isset($unit['interval']) && $unit['interval'] == 'every-hour') {
                        // Check if the unit has not already been launched
                        if (!in_array($unitName, $launchedUnits)) {
                            $this->runUnit($unit['title'], $unitName, isset($unit['force']) ? $unit['force'] : false);

                            // Add the unit to the list of launched units, to avoid launching it twice in this loop
                            $launchedUnits[] = $unitName;
                        }
                    }
                }
            }

            /**
             *  Run tasks scheduled to run every days at a specific time
             */
            foreach ($units as $unitName => $unit) {
                if (isset($unit['interval']) && $unit['interval'] == 'every-day' && $unit['time'] == $currentTime) {
                    // Check if the unit has not already been launched
                    if (!in_array($unitName, $launchedUnits)) {
                        $this->runUnit($unit['title'], $unitName, isset($unit['force']) ? $unit['force'] : false);

                        // Add the unit to the list of launched units, to avoid launching it twice in this loop
                        $launchedUnits[] = $unitName;
                    }
                }
            }

            /**
             *  Run tasks scheduled to run every weeks on a specific day at a specific time
             */
            foreach ($units as $unitName => $unit) {
                if (isset($unit['interval']) && $unit['interval'] == 'every-week' && isset($unit['day']) && $unit['day'] == strtolower(date('l')) && isset($unit['time']) && $unit['time'] == $currentTime) {
                    // Check if the unit has not already been launched
                    if (!in_array($unitName, $launchedUnits)) {
                        $this->runUnit($unit['title'], $unitName, isset($unit['force']) ? $unit['force'] : false);

                        // Add the unit to the list of launched units, to avoid launching it twice in this loop
                        $launchedUnits[] = $unitName;
                    }
                }
            }

            /**
             *  Finally, run tasks scheduled to run every minutes
             */
            foreach ($units as $unitName => $unit) {
                if (isset($unit['interval']) && $unit['interval'] == 'every-minute') {
                    // Check if the unit has not already been launched
                    if (!in_array($unitName, $launchedUnits)) {
                        $this->runUnit($unit['title'], $unitName, isset($unit['force']) ? $unit['force'] : false);

                        // Add the unit to the list of launched units, to avoid launching it twice in this loop
                        $launchedUnits[] = $unitName;
                    }
                }
            }

            pcntl_signal_dispatch();

            // This is not the first loop anymore, set startup to false
            $startup = false;

            // Calculate sleep time to wake up at the beginning of the next minute
            sleep(60 - (date('s')));
        }
    }

    /**
     *  Run this service with the specified unit name
     */
    private function runUnit(string $title, string $unit, bool $force = false) : void
    {
        try {
            /**
             *  Check if the service with specified name is already running to avoid running it twice
             *  A php process must be running
             *
             *  If force != false, then the service will be run even if it is already running (e.g: for running multiple scheduled tasks at the same time)
             */
            if ($force === false) {
                $myprocess = new \Controllers\Process('/usr/bin/ps -eo command | grep "^repomanager.' . $unit . '" | grep -v grep');
                $myprocess->execute();
                $myprocess->close();

                /**
                 *  Quit if there is already a process running
                 */
                if ($myprocess->getExitCode() == 0) {
                    return;
                }
            }

            /**
             *  Else, run the service with the specified unit name
             */
            if (DEBUG_MODE) {
                parent::logDebug('Running: ' . $title . '...');
            }

            $myprocess = new \Controllers\Process("/usr/bin/php " . ROOT . "/tools/service.php '" . $unit . "' >/dev/null 2>/dev/null &");
            $myprocess->execute();
            $output = $myprocess->getOutput();
            $myprocess->close();

            if ($myprocess->getExitCode() != 0) {
                throw new Exception($output);
            }
        } catch (Exception $e) {
            parent::logError('Error while launching ' . $title . ' (service unit ' . $parameter . '): ' . $e->getMessage());
        }
    }
}
