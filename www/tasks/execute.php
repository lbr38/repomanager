<?php
cli_set_process_title('repomanager.task-run');

define('ROOT', '/var/www/repomanager');
require_once(ROOT . "/controllers/Autoloader.php");
new \Controllers\Autoloader('api');

ini_set('memory_limit', TASK_EXECUTION_MEMORY_LIMIT . 'M');

$myTask = new \Controllers\Task\Task();
$mylog = new \Controllers\Log\Log();
$myFatalErrorHandler = new \Controllers\FatalErrorHandler();

/**
 *  Getting options from command line: task Id is required and cannot be empty.
 *
 *  First parameter passed to getopt is null: we don't want to work with short options.
 *  More infos about getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt(null, ["id:"]);

try {
    /**
     *  Retrieve task Id
     */
    if (empty($getOptions['id'])) {
        throw new Exception('Task Id is not defined');
    }

    $taskId = $getOptions['id'];

    /**
     *  Set task Id for fatal error handler
     */
    $myFatalErrorHandler->setTaskId($taskId);

    /**
     *  Retrieve task details
     */
    $taskParams = $myTask->getById($taskId);

    if (empty($taskParams)) {
        throw new Exception('Cannot get task details from task #' . $taskId . ': empty results.');
    }

    $taskParams = json_decode($taskParams['Raw_params'], true);

    if (empty($taskParams['action'])) {
        throw new Exception('Action not specified');
    }

    /**
     *  Generate controller name
     */
    $controllerPath = '\Controllers\Task\Repo\\' . ucfirst($taskParams['action']);

    /**
     *  Check if class exists, otherwise the action might be invalid
     */
    if (!class_exists($controllerPath)) {
        throw new Exception('Invalid action: ' . $taskParams['action']);
    }

    /**
     *  Instantiate controller and execute action
     */
    $controller = new $controllerPath($taskId);
    $controller->execute();

/**
 *  Catch exceptions
 */
} catch (Exception $e) {
    $mylog->log('error', 'An exception error occured while running task #' . $taskId, $e->getMessage());
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    exit(1);

/**
 *  Catch fatal errors
 */
} catch (Error $e) {
    $mylog->log('error', 'Fatal error occured while running task #' . $taskId, $e->getMessage());
    echo 'Fatal error: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}

exit(0);
