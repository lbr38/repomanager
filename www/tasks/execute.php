#!/usr/bin/env php
<?php
use \Controllers\Log\Log;
use \Controllers\Autoloader;
use \Controllers\FatalErrorHandler;
use \Controllers\Task\Task;
use \Controllers\Task\Queue;
use \Controllers\App\Main as AppLoader;

/**
 *  Execute a task, this process is started by the tasks queue (see \Controllers\Task\Queue)
 *  It first claims its task, and exits immediately if the task is not to be started now
 */

// Process title until the task is claimed, it then includes the task Id (see \Controllers\Task\Queue::claim())
cli_set_process_title('repomanager.task-run');

// Load configuration
define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new Autoloader();
new AppLoader('minimal');

/**
 *  Getting options from command line: the Id of the task to run
 *  More infos about getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt('', ["id:"]);

if (empty($getOptions['id']) or !ctype_digit($getOptions['id'])) {
    echo 'A task Id must be specified with --id' . PHP_EOL;
    exit(1);
}

$taskId = (int) $getOptions['id'];
$claimed = false;
$failure = '';

/**
 *  Once the task is claimed, whatever the way this process ends (end of the task, uncaught error, fatal error...),
 *  the task is marked as failed if it has not been ended properly, and its slot is given to the next queued task.
 *  Registered before the fatal error handler, which exits and would prevent this function from running.
 */
register_shutdown_function(function () use ($taskId, &$claimed, &$failure) {
    if (!$claimed) {
        return;
    }

    // The process may end because of a fatal error (e.g. memory exhausted), some memory is needed to release the task
    $error = error_get_last();

    if ($error !== null and in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        ini_set('memory_limit', '-1');
        $failure = 'Fatal error: ' . $error['message'];
    }

    try {
        (new Queue())->release($taskId, $failure);
    } catch (Exception | Error $e) {
        echo 'Error while releasing task #' . $taskId . ': ' . $e->getMessage() . PHP_EOL;
    }
});

$taskController = new Task();
$logController = new Log();
$fatalErrorHandlerController = new FatalErrorHandler();
$fatalErrorHandlerController->setTaskId($taskId);

try {
    /**
     *  Claim the task, it may have been stopped, deleted, started by another process, or there is no slot available for it anymore
     *  Exits with a specific code, so that a manual execution (e.g. in CI) does not look successful
     */
    if (!(new Queue())->claim($taskId)) {
        echo 'Task #' . $taskId . ' is not to be started now. Exiting...' . PHP_EOL;
        exit(2);
    }

    $claimed = true;

    // Retrieve task details
    $task = $taskController->getById($taskId);

    if (empty($task)) {
        throw new Exception('Cannot get task details from task #' . $taskId . ': empty results.');
    }

    try {
        $taskRawParams = json_decode($task['Raw_params'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('Cannot decode task params from task #' . $taskId . ': ' . $e->getMessage());
    }

    if (empty($taskRawParams['action'])) {
        throw new Exception('Action not specified');
    }

    // Generate controller name based on the action specified in the task parameters
    $controllerPath = '\Controllers\Repo\Task\\' . ucfirst($taskRawParams['action']);

    // Mirrors tasks have their own controllers
    if (($taskRawParams['domain'] ?? '') == 'mirror') {
        $controllerPath = '\Controllers\Mirror\Task\\' . ucfirst($taskRawParams['action']);
    }

    // Check if class exists, otherwise the action might be invalid
    if (!class_exists($controllerPath)) {
        throw new Exception('Invalid action: ' . $taskRawParams['action']);
    }

    // Set memory limit for task execution
    ini_set('memory_limit', TASK_EXECUTION_MEMORY_LIMIT . 'M');

    // Instantiate the controller for the task
    echo 'Task #' . $taskId . ' is running...' . PHP_EOL;
    $controller = new $controllerPath($taskId);
    echo 'Task #' . $taskId . ' ended' . PHP_EOL;

// Handle any exceptions that occur during task execution
} catch (Exception $e) {
    $failure = $e->getMessage();
    echo 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL;
    echo 'Task #' . $taskId . ' failed' . PHP_EOL;
    exit(1);

// Handle any fatal errors that occur during task execution
} catch (Error $e) {
    $failure = $e->getMessage();
    $logController->log('error', 'A fatal error occurred while running task #' . $taskId, $e->getMessage(), $e->getTraceAsString());
    echo 'Fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL;
    echo 'Task #' . $taskId . ' failed' . PHP_EOL;
    exit(1);
}

exit(0);
