<?php

define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('api');

$mySignalHandler = new \Controllers\SignalHandler();
$myService = new \Controllers\Service\Service();
$myStatService = new \Controllers\Service\Statistic();
$myScheduledTaskService = new \Controllers\Service\ScheduledTask();
$myCveController = new \Controllers\Cve\Tools\Import();

/**
 *  Define a file to create on interrupt
 *  This file is used to stop stats parsing
 */
$mySignalHandler->touchFileOnInterrupt(DATA_DIR . '/.service-parsing-stop');

/**
 *  Run stats access log parsing task
 */
if (!empty($argv[1]) && $argv[1] == 'stats/accesslog/parse') {
    $myStatService->parseAccessLog();
    exit;
}

/**
 *  Run stats access log processing task
 */
if (!empty($argv[1]) && $argv[1] == 'stats/accesslog/process') {
    $myStatService->processAccessLog();
    exit;
}

/**
 *  Run scheduled tasks
 */
if (!empty($argv[1]) && $argv[1] == 'scheduled-task-exec') {
    $myScheduledTaskService->execute();
    exit;
}

/**
 *  Run scheduled tasks reminder
 */
if (!empty($argv[1]) && $argv[1] == 'scheduled-task-reminder') {
    $myScheduledTaskService->sendReminders();
    exit;
}

/**
 *  Run CVE import task
 */
if (!empty($argv[1]) && $argv[1] == 'cve-import') {
    $myCveController->import();
    exit;
}

/**
 *  Run main service
 */
$myService->run();

exit;
