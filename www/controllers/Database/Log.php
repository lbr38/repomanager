<?php

namespace Controllers\Database;

use Controllers\Log\File as FileLog;
use Controllers\Log\Log as LogController;
use Controllers\Layout\ContainerReload;
use Exception;
use Throwable;

class Log
{
    /**
     *  Log a database error message
     */
    public static function error(Throwable $exception) : void
    {
        $logController             = new LogController();
        $containerReloadController = new ContainerReload();

        // Log to Web UI
        $logController->log('error', 'Database', 'An error occurred while executing request in database.', $exception->getMessage() . PHP_EOL . $exception->getTraceAsString());

        // Additionally log to database error log file
        FileLog::error(DB_LOGS_DIR . '/' . date('Y-m-d') . '_error.log', $exception->getMessage() . PHP_EOL . $exception->getTraceAsString());

        // Force reload of 'logs' container in Web UI
        $containerReloadController->reload('header/general-log-messages');

        // Throw exception to stop execution flow
        throw new Exception('Database error occurred.');
    }
}
