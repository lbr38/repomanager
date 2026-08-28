<?php

define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader();
new \Controllers\App\Main('minimal');

use \Controllers\Service\Execute as ServiceExecution;
use \Controllers\Log\Cli as CliLog;
use \Controllers\Log\Log;

try {
    $logController = new Log();

    // Execute service unit, or main service if no unit provided
    new ServiceExecution($argv[1] ?? 'main');
} catch (Exception | Error $e) {
    CliLog::error('Background service general error', $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
    $logController->log('error', 'Background service', 'General error: ' . $e->getMessage(), $e->getTraceAsString());
    exit(1);
}

exit;
