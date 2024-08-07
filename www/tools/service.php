<?php
cli_set_process_title('repomanager.service');

define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('api');

$myFatalErrorHandler = new \Controllers\FatalErrorHandler();
$mySignalHandler = new \Controllers\SignalHandler();
$myService = new \Controllers\Service\Service();
$myStatService = new \Controllers\Service\Statistic();
$myScheduledTaskService = new \Controllers\Service\ScheduledTask();
$myCveController = new \Controllers\Cve\Tools\Import();
$myLogController = new \Controllers\Log\Log();

try {
    /**
     *  Define a file to create on interrupt
     *  This file is used to stop stats parsing
     */
    $mySignalHandler->touchFileOnInterrupt(DATA_DIR . '/.service-parsing-stop');

    /**
     *  Run websocket server
     */
    if (!empty($argv[1]) && $argv[1] == 'wss') {
        cli_set_process_title('repomanager.wss');

        /**
         *  Start websocket server on port 8081
         */
        new \Controllers\Websocket\WebsocketServer(8081);
        exit;
    }

    /**
     *  Run stats access log parsing task
     */
    if (!empty($argv[1]) && $argv[1] == 'stats-parse') {
        cli_set_process_title('repomanager.stats-parse');
        $myStatService->parseAccessLog();
        exit;
    }

    /**
     *  Run stats access log processing task
     */
    if (!empty($argv[1]) && $argv[1] == 'stats-process') {
        cli_set_process_title('repomanager.stats-process');
        $myStatService->processAccessLog();
        exit;
    }

    /**
     *  Run scheduled tasks
     */
    if (!empty($argv[1]) && $argv[1] == 'scheduled-task-exec') {
        cli_set_process_title('repomanager.scheduled-task-exec');
        $myScheduledTaskService->execute();
        exit;
    }

    /**
     *  Run scheduled tasks reminder
     */
    if (!empty($argv[1]) && $argv[1] == 'scheduled-task-reminder') {
        cli_set_process_title('repomanager.scheduled-task-reminder');
        $myScheduledTaskService->sendReminders();
        exit;
    }

    /**
     *  Run CVE import task
     */
    if (!empty($argv[1]) && $argv[1] == 'cve-import') {
        cli_set_process_title('repomanager.cve-import');
        $myCveController->import();
        exit;
    }

    /**
     *  Run main service
     */
    $myService->run();
} catch (Exception $e) {
    $myLogController->log('error', 'Service', "General exception: " . $e->getMessage());
    exit(1);
} catch (Error $e) {
    $myLogController->log('error', 'Service', "General error: " . $e->getMessage());
    exit(1);
}

exit;
