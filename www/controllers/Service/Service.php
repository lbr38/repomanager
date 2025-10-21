<?php

namespace Controllers\Service;

use Exception;
use Controllers\App\DebugMode;
use Controllers\Utils\Convert;
use Controllers\Log\Cli as CliLog;
use Controllers\Log\File as FileLog;

class Service
{
    protected $unit;
    private $logDir;
    private $log;
    private $logController;
    private $fatalErrorHandler;

    public function __construct(string $unit)
    {
        $this->fatalErrorHandler = new \Controllers\FatalErrorHandler();
        $this->logController     = new \Controllers\Log\Log();

        // Load service units configuration
        include(ROOT . '/config/service-units.php');

        // Set the unit name
        $this->unit   = $unit;

        // If a custom log dir is defined for the unit, use it, else use the unit name
        $this->logDir = $units[$unit]['log-dir'] ?? $unit;

        // Set the log file path
        $this->log    = $this->getLogFile();

        // Create parent dir if not exists
        if (!is_dir(SERVICE_LOGS_DIR . '/' . $this->logDir)) {
            if (!mkdir(SERVICE_LOGS_DIR . '/' . $this->logDir, 0770, true)) {
                $this->logController->log('error', 'Service', 'Could not create service unit log dir: ' . SERVICE_LOGS_DIR . '/' . $this->logDir);
            }
        }
    }

    /**
     *  Return repomanager settings
     *  A specific setting can be requested by passing its name as argument
     */
    public static function getSettings(string $setting = null) : array|string
    {
        $settingsController = new \Controllers\Settings();

        $settings = $settingsController->get();

        if (!empty($setting)) {
            if (!array_key_exists($setting, $settings)) {
                throw new Exception('Unable to retrieve setting ' . $setting);
            }

            return $settings[$setting];
        }

        return $settings;
    }

    /**
     *  Return repomanager service status
     */
    public static function isRunning(string $unit = 'main') : bool
    {
        $myprocess = new \Controllers\Process('/usr/bin/ps -eo command  | grep "^repomanager.' . $unit . '" | grep -v grep');
        $myprocess->execute();
        $myprocess->close();

        if ($myprocess->getExitCode() != 0) {
            return false;
        }

        return true;
    }

    /**
     *  Log message to console and to log file
     */
    public function log(string $message) : void
    {
        CliLog::log($message);
        FileLog::log($this->getLogFile(), $message);
    }

    /**
     *  Log error message to console and to log file
     */
    public function logError(string $message) : void
    {
        CliLog::error('Service', $message);
        FileLog::error($this->getLogFile(), $message);
    }

    /**
     *  Log debug message to console and to log file if debug mode is enabled
     */
    public function logDebug(string $message) : void
    {
        if (!DebugMode::enabled()) {
            return;
        }

        CliLog::debug($message);
        FileLog::debug($this->getLogFile(), $message);
    }

    /**
     *  Return the log file path for this service unit
     *  This is useful to always get the correct log file path even if the date changed
     */
    private function getLogFile() : string
    {
        return SERVICE_LOGS_DIR . '/' . $this->logDir . '/' . date('Y-m-d') . '-' . $this->unit . '.log';
    }
}
