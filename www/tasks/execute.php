#!/usr/bin/env php
<?php
// Set process title for task execution
cli_set_process_title('repomanager.task-run');

// Load configuration
define('ROOT', '/var/www/repomanager');
require_once(ROOT . '/controllers/Autoloader.php');
new \Controllers\Autoloader();
new \Controllers\App\Main('minimal');

// Set memory limit for task execution
ini_set('memory_limit', TASK_EXECUTION_MEMORY_LIMIT . 'M');

$mysettings = new \Controllers\Settings();
$myTask = new \Controllers\Task\Task();
$taskListingController = new \Controllers\Task\Listing();
$mylog = new \Controllers\Log\Log();
$myFatalErrorHandler = new \Controllers\FatalErrorHandler();

/**
 *  Getting options from command line: a task Id can be provided to run a specific task.
 *
 *  First parameter passed to getopt is null: we don't want to work with short options.
 *  More infos about getopt() : https://blog.pascal-martin.fr/post/php-5.3-getopt-parametres-ligne-de-commande/
 */
$getOptions = getopt(null, ["id:"]);

try {
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
        $taskId = $myTask->getLastTaskId('queued');

        // If no task Id has been found, throw an exception
        if (empty($taskId)) {
            echo 'No task to run.' . PHP_EOL;
            exit(2);
        }
    }

    /**
     *  Set task Id for fatal error handler
     */
    $myFatalErrorHandler->setTaskId($taskId);

    /**
     *  Retrieve task details
     */
    $task = $myTask->getById($taskId);

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

    /**
     *  Generate controller name
     */
    $controllerPath = '\Controllers\Repo\Task\\' . ucfirst($taskRawParams['action']);

    /**
     *  Check if class exists, otherwise the action might be invalid
     */
    if (!class_exists($controllerPath)) {
        throw new Exception('Invalid action: ' . $taskRawParams['action']);
    }

    while (true) {
        /**
         *  Get settings
         */
        try {
            $settings = $mysettings->get();
        } catch (Exception $e) {
            throw new Exception('Cannot get global settings: ' . $e->getMessage());
        }

        /**
         *  If task queuing is disabled, run the task immediately
         */
        if ($settings['TASK_QUEUING'] == 'false') {
            break;
        }

        /**
         *  If task queuing is enabled and the maximum number of simultaneous tasks is set, check if the task can be started
         */
        if ($settings['TASK_QUEUING'] == 'true' and !empty($settings['TASK_QUEUING_MAX_SIMULTANEOUS'])) {
            /**
             *  Get running tasks
             */
            $runningTasks = $taskListingController->getExecutable('running');

            /**
             *  Get all currently queued tasks
             */
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

    /**
     *  Instantiate controller and execute action
     */
    echo 'Task #' . $taskId . ' is running...' . PHP_EOL;
    $controller = new $controllerPath($taskId);
    echo 'Task #' . $taskId . ' ended' . PHP_EOL;

/**
 *  Catch exceptions
 */
} catch (Exception $e) {
    // $mylog->log('error', 'An exception error occurred while running task #' . $taskId, $e->getMessage(), $e->getTraceAsString());
    echo 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL;
    echo 'Task #' . $taskId . ' failed' . PHP_EOL;
    exit(1);

/**
 *  Catch fatal errors
 */
} catch (Error $e) {
    $mylog->log('error', 'A fatal error occurred while running task #' . $taskId, $e->getMessage(), $e->getTraceAsString());
    echo 'Fatal error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine() . PHP_EOL;
    echo 'Task #' . $taskId . ' failed' . PHP_EOL;
    exit(1);
}

exit(0);
