<?php

define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('api');

$mylog = new \Controllers\Log\Log();

/**
 *  Get the arguments passed to this script
 */

/**
 *  PID of the operation
 */
if (empty($argv[1])) {
    $mylog->log('error', 'Logbuilder run', 'No PID provided');
    exit(1);
}
$pid = $argv[1];

/**
 *  Location of the main log file (logs/main/repomanager...)
 */
if (empty($argv[2])) {
    $mylog->log('error', 'Logbuilder run', 'No log file provided');
    exit(1);
}
$logFile = $argv[2];

/**
 *  Adding the PID of this script to the main PID file
 */
file_put_contents(PID_DIR . '/' . $pid . '.pid', 'SUBPID="' . getmypid() . '"' . PHP_EOL, FILE_APPEND);

try {
    $myOperationLog = new \Controllers\Log\OperationLog('repomanager', $pid);
    $myOperationLog->logBuilder($pid, $logFile);
} catch (\Exception $e) {
    $mylog->log('error', 'Logbuilder run', $e->getMessage());
    exit(1);
}

exit(0);
