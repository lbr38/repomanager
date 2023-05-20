<?php

define('ROOT', dirname(__FILE__, 2));
require_once(ROOT . '/controllers/Autoloader/Autoloader.php');
\Controllers\Autoloader\Autoloader::api();

$mylog = new \Controllers\Log\OperationLog();

/**
 *  Get the arguments passed to this script
 */

/**
 *  PID of the operation
 */
if (!empty($argv[1])) {
    $pid = $argv[1];
}

/**
 *  Location of the main log file (logs/main/repomanager...)
 */
if (!empty($argv[2])) {
    $logFile = $argv[2];
}

/**
 *  Total number of steps
 */
if (!empty($argv[3])) {
    $steps = $argv[3];
}

/**
 *  Adding the PID of this script to the main PID file
 */
$mypid = getmypid();
file_put_contents(PID_DIR . '/' . $pid . '.pid', 'SUBPID="' . $mypid . '"' . PHP_EOL, FILE_APPEND);


try {
    $mylog->logBuilder($pid, $logFile, $steps);
} catch (\Exception $e) {
    echo $e->getMessage();
    exit(1);
}

exit(0);
