<?php
define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader();
new \Controllers\App\Main('minimal');
use \Controllers\Log\Cli as CliLog;

try {
    $logController = new \Controllers\Log\Log();

    // Execute service unit, or main service if no unit provided
    new \Controllers\Service\Execute($argv[1] ?? 'main');
} catch (Exception | Error $e) {
    CliLog::error('Background service general error', $e->getMessage() . PHP_EOL . $e->getTraceAsString() . PHP_EOL);
    $logController->log('error', 'Background service', 'General error: ' . $e->getMessage(), $e->getTraceAsString());
    exit(1);
}

exit;
