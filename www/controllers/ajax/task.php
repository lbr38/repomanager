<?php
$taskController = new \Controllers\Task\Task();

/**
 *  Validate and execute a task form
 */
if ($_POST['action'] == 'validate-execute' and !empty($_POST['taskParams'])) {
    $taskFormController = new \Controllers\Task\Form\Form();

    try {
        try {
            $taskRawParams = json_decode($_POST['taskParams'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode task parameters: ' . $e->getMessage());
        }

        $taskFormController->validate($taskRawParams);
        $task = $taskController->execute($taskRawParams);

        // If the task is an array, it means that multiple tasks have been executed, redirect to the tasks page
        if (is_array($task)) {
            $count = count($task);
            $link = '/tasks';
        }

        // If there is only one task, redirect to the task log page
        if (is_int($task)) {
            $count = 1;
            $link = '/task/' . $task;
        }

        if (isset($taskRawParams[0]['schedule']['scheduled']) and $taskRawParams[0]['schedule']['scheduled'] == 'true') {
            response(HTTP_OK, 'Task' . ($count > 1 ? 's are scheduled' : ' is scheduled') . ': <a href="/tasks" target="_blank" rel="noopener noreferrer"><b>view</b></a>');
        }

        response(HTTP_OK, 'Task' . ($count > 1 ? 's are running' : ' is running') . ': <a href="' . $link . '" target="_blank" rel="noopener noreferrer"><b>view</b></a>');
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }
}

/**
 *  Validate and schedule a task targeting a dynamic set of repositories (all latest snapshots matching filters)
 */
if ($_POST['action'] == 'validate-execute-target' and !empty($_POST['taskParams'])) {
    $taskFormController = new \Controllers\Task\Form\Form();

    try {
        try {
            $taskRawParams = json_decode($_POST['taskParams'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode task parameters: ' . $e->getMessage());
        }

        // Validate the task and retrieve the sanitized parameters
        $taskRawParams = $taskFormController->validateTarget($taskRawParams);

        // Create the scheduled task, it will not be executed now
        $taskController->execute([$taskRawParams]);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task is scheduled: <a href="/tasks" target="_blank" rel="noopener noreferrer"><b>view</b></a>');
}

/**
 *  Return a description of the repositories currently matching a target definition
 */
if ($_POST['action'] == 'count-target-repos' and isset($_POST['target'])) {
    $targetController = new \Controllers\Task\Target();

    try {
        try {
            $target = json_decode($_POST['target'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode target: ' . $e->getMessage());
        }

        $repos = $targetController->resolve($target);
        $count = count($repos);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Target: ' . \Controllers\Task\Target::describe($target) . '. ' . $count . ' repositor' . ($count == 1 ? 'y' : 'ies') . ' currently matching.');
}

/**
 *  Edit a scheduled task
 */
if ($_POST['action'] == 'edit-scheduled-tasks' and !empty($_POST['tasks'])) {
    $scheduledTaskController = new \Controllers\Task\Scheduled();

    try {
        try {
            $tasks = json_decode($_POST['tasks'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode task parameters: ' . $e->getMessage());
        }

        $scheduledTaskController->edit($tasks);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task' . (count($tasks) > 1 ? 's have' : ' has') . ' been updated');
}

/**
 *  Disable task execution
 */
if ($_POST['action'] == 'disable' and !empty($_POST['id'])) {
    try {
        $taskController->disable($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task execution disabled');
}

/**
 *  Enable task execution
 */
if ($_POST['action'] == 'enable' and !empty($_POST['id'])) {
    try {
        $taskController->enable($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task execution enabled');
}

/**
 *  Delete a scheduled task
 */
if ($_POST['action'] == 'delete' and !empty($_POST['id'])) {
    try {
        $taskController->delete($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task' . (is_array($_POST['id']) and count($_POST['id']) > 1 ? 's have' : ' has') . ' been deleted');
}

/**
 *  Relaunch a task
 */
if ($_POST['action'] == 'relaunch' and !empty($_POST['id'])) {
    try {
        $taskController->relaunch($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task' . (is_array($_POST['id']) and count($_POST['id']) > 1 ? 's have' : ' has') . ' been relaunched using the same parameters');
}

/**
 *  Stop a task
 */
if ($_POST['action'] == 'stop' and !empty($_POST['id'])) {
    try {
        $taskController->stop($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task' . (is_array($_POST['id']) and count($_POST['id']) > 1 ? 's have' : ' has') . ' been stopped');
}

/**
 *  Get and return task steps status (JSON)
 */
if ($_POST['action'] == 'get-steps' and !empty($_POST['taskId'])) {
    try {
        $taskStepController = new \Controllers\Task\Step($_POST['taskId']);
        $content = $taskStepController->getSteps();
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get and return the content of a specific task step, in a specific order depending if autoscroll is enabled
 */
if ($_POST['action'] == 'get-step-content' and !empty($_POST['taskId']) and !empty($_POST['stepIdentifier']) and !empty($_POST['autoscroll'])) {
    try {
        $taskStepController = new \Controllers\Task\Step($_POST['taskId']);
        $content = $taskStepController->getStepContent($_POST['stepIdentifier'], $_POST['autoscroll']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get and return previous or next log lines of a specific task step
 */
if ($_POST['action'] == 'get-log-lines' and !empty($_POST['taskId']) and !empty($_POST['step']) and !empty($_POST['direction']) and isset($_POST['key'])) {
    try {
        $taskStepController = new \Controllers\Task\Step($_POST['taskId']);
        $content = $taskStepController->getLogLines($_POST['step'], $_POST['direction'], $_POST['key']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

if ($_POST['action'] == 'get-task-status' and !empty($_POST['taskId'])) {
    try {
        $task = $taskController->getById($_POST['taskId']);
        $status = $task['Status'];
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $status);
}

/**
 *  Get and return the content of a task process log file (for debugging purpose)
 */
if ($action == 'get-task-process-log' and !empty($_POST['id'])) {
    try {
        if (!is_numeric($_POST['id'])) {
            throw new Exception('Invalid task id');
        }

        $logfile = MAIN_LOGS_DIR . '/repomanager-task-' . $_POST['id'] . '-log.process';

        // Check if the process log file exists
        if (!file_exists($logfile)) {
            throw new Exception('Log file not found');
        }

        // Get the log content
        $content = file_get_contents($logfile);

        // Check if the log content was read successfully
        if ($content === false) {
            response(HTTP_BAD_REQUEST, 'Unable to read log file');
        }
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
