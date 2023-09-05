<?php

define('ROOT', dirname(__FILE__, 2));
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('api');

$mysignalhandler = new \Controllers\SignalHandler();
$myservice = new \Controllers\Service\Service();
$myservicestat = new \Controllers\Service\Statistic();
$myserviceplan = new \Controllers\Service\Planification();

/**
 *  Define a file to create on interrupt
 *  This file is used to stop stats parsing
 */
$mysignalhandler->touchFileOnInterrupt(DATA_DIR . '/.service-parsing-stop');

/**
 *  Run stats' access log parsing task
 */
if (!empty($argv[1]) && $argv[1] == 'logparser') {
    $myservicestat->statsParseAccessLog();
    exit;
}

/**
 *  Run planification task
 */
if (!empty($argv[1]) && $argv[1] == 'plan-exec') {
    $myserviceplan->planExecute();
    exit;
}

/**
 *  Run planification reminder task
 */
if (!empty($argv[1]) && $argv[1] == 'plan-reminder') {
    $myserviceplan->planReminder();
    exit;
}

/**
 *  Run main service
 */
$myservice->run();

exit;
