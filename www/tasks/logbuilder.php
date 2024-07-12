<?php
cli_set_process_title('repomanager.logbuilder');

define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader('api');

$mylog = new \Controllers\Log\Log();
$myTask = new \Controllers\Task\Task();

try {
    /**
     *  Get the arguments passed to this script
     */

    /**
     *  Retrieve task Id
     */
    if (empty($argv[1])) {
        throw new Exception('error', 'Logbuilder run', 'No task Id provided');
    }
    $taskId = $argv[1];

    /**
     *  Rewrite the process title
     */
    cli_set_process_title('repomanager.task-' . $taskId . '.logbuilder');

    /**
     *  Retrieve location of the main log file (logs/main/...)
     */
    if (empty($argv[2])) {
        throw new Exception('error', 'Logbuilder run', 'No log file provided');
    }
    $logFile = $argv[2];

    /**
     *  Retrieve task PID
     */
    $taskPid = $myTask->getPidById($taskId);

    if (empty($taskPid)) {
        throw new Exception('error', 'Logbuilder run', 'Cannot retrieve task PID from task #' . $pid . ': empty results.');
    }

    /**
     *  Adding the PID of this script to the main PID file
     */
    if (!file_put_contents(PID_DIR . '/' . $taskPid . '.pid', 'SUBPID="' . getmypid() . '"' . PHP_EOL, FILE_APPEND)) {
        throw new Exception('error', 'Logbuilder run', 'Cannot write subpid to pid file');
    }

    $myTaskLog = new \Controllers\Task\Log($taskId);
    $myTaskLog->logBuilder($taskId, $logFile);
} catch (Exception $e) {
    $mylog->log('error', 'Logbuilder run', $e->getMessage());
    exit(1);
}

exit(0);
