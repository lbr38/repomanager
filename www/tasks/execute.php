#!/usr/bin/env php
<?php
use \Controllers\Log\Log;
use \Controllers\Settings;
use \Controllers\Autoloader;
use \Controllers\FatalErrorHandler;
use \Controllers\Task\Listing as TaskListing;
use \Controllers\Task\Task;
use \Controllers\App\Main as AppLoader;

// Set process title for task execution
cli_set_process_title('repomanager.task-run');

// Load configuration
define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new Autoloader();
new AppLoader('minimal');

// Set memory limit for task execution
// ini_set('memory_limit', TASK_EXECUTION_MEMORY_LIMIT . 'M');

$settingsController = new Settings();
$taskController = new Task();
$taskListingController = new TaskListing();
$logController = new Log();
$fatalErrorHandlerController = new FatalErrorHandler();

/**
 *  Getting options from command line: a task Id can be provided to run a specific task.
 *
 *  First parameter passed to getopt is null: we don't want to work with short options.
 *  More infos about getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt(null, ["id:"]);

try {
    // TODO debug
    ini_set('memory_limit', '16M');
    echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;

    /**
     *  If a task Id is provided, use it.
     *  Otherwise, retrieve the latest task Id from the database.
     */
    if (!empty($getOptions['id'])) {
        if (!is_numeric($getOptions['id'])) {
            throw new Exception('Task Id must be a number.');
        }

        $taskId = (int) $getOptions['id'];
    } else {
        // Retrieve latest task Id
        $taskId = $taskController->getLastTaskId('queued');

        // If no task Id has been found, throw an exception
        if (empty($taskId)) {
            echo 'No task to run.' . PHP_EOL;
            exit(2);
        }
    }

    // Set task Id for fatal error handler
    $fatalErrorHandlerController->setTaskId($taskId);

    // Retrieve task details
    $task = $taskController->getById($taskId);

    if (empty($task)) {
        throw new Exception('Cannot get task details from task #' . $taskId . ': empty results.');
    }

    // Update the process title to include the task Id
    cli_set_process_title('repomanager.task-run.' . $taskId);

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

    // Check if class exists, otherwise the action might be invalid
    if (!class_exists($controllerPath)) {
        throw new Exception('Invalid action: ' . $taskRawParams['action']);
    }

    while (true) {
        // Get global settings
        try {
            $settings = $settingsController->get();
        } catch (Exception $e) {
            throw new Exception('Cannot get global settings: ' . $e->getMessage());
        }

        // If task queuing is disabled, run the task immediately
        if ($settings['TASK_QUEUING'] == 'false') {
            break;
        }

        // If task queuing is enabled and the maximum number of simultaneous tasks is set, check if the task can be started
        if ($settings['TASK_QUEUING'] == 'true' and !empty($settings['TASK_QUEUING_MAX_SIMULTANEOUS'])) {
            // Get all currently running tasks
            $runningTasks = $taskListingController->getExecutable('running');

            // Get all currently queued tasks
            $queuedTasks = $taskListingController->getExecutable('queued');

            /**
             *  First, retrieve the position of this task in the queue.
             *  The queued task may have been cancelled by the user, so we don't want to run it if it's not in the queued tasks list anymore.
             */
            $queuePosition = array_search($taskId, array_column($queuedTasks, 'Id'));

            if ($queuePosition === false) {
                echo 'Task #' . $taskId . ' is not in the queued tasks list anymore. Exiting...' . PHP_EOL;
                exit(2);
            }

            /**
             *  The queue is already ordered by priority, so the task can be started as soon as it is
             *  among the first ones for which a slot is available.
             */
            if ($queuePosition < ($settings['TASK_QUEUING_MAX_SIMULTANEOUS'] - count($runningTasks))) {
                break;
            }

            echo 'Maximum number of simultaneous tasks reached (' . $settings['TASK_QUEUING_MAX_SIMULTANEOUS'] . '). Waiting for a task to finish...' . PHP_EOL;
        }

        sleep(5);
    }

    // Set memory limit for task execution
    ini_set('memory_limit', TASK_EXECUTION_MEMORY_LIMIT . 'M');

    // TODO debug
    echo 'Memory limit: ' . ini_get('memory_limit') . PHP_EOL;

    // Instantiate the controller for the task
    echo 'Task #' . $taskId . ' is running...' . PHP_EOL;
    $controller = new $controllerPath($taskId);
    echo 'Task #' . $taskId . ' ended' . PHP_EOL;

// Handle any exceptions that occur during task execution
} catch (Exception $e) {
    // $logController->log('error', 'An exception error occurred while running task #' . $taskId, $e->getMessage(), $e->getTraceAsString());
    echo 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL;
    echo 'Task #' . $taskId . ' failed' . PHP_EOL;
    exit(1);

// Handle any fatal errors that occur during task execution
} catch (Error $e) {
    $logController->log('error', 'A fatal error occurred while running task #' . $taskId, $e->getMessage(), $e->getTraceAsString());
    echo 'Fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL;
    echo 'Task #' . $taskId . ' failed' . PHP_EOL;
    exit(1);
}

exit(0);
