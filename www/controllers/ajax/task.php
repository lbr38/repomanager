<?php
$myTask = new \Controllers\Task\Task();

/**
 *  Validate and execute a task form
 */
if ($_POST['action'] == 'validateForm' and !empty($_POST['taskParams'])) {
    $myTaskForm = new \Controllers\Task\Form\Form();

    try {
        try {
            $taskRawParams = json_decode($_POST['taskParams'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode task parameters: ' . $e->getMessage());
        }

        $myTaskForm->validate($taskRawParams);
        $myTask->execute($taskRawParams);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    if (isset($taskRawParams[0]['schedule']['scheduled']) and $taskRawParams[0]['schedule']['scheduled'] == 'true') {
        response(HTTP_OK, 'Task is scheduled: <a href="/run" target="_blank" rel="noopener noreferrer"><b>visualize</b></a>');
    }

    response(HTTP_OK, 'Task is running: <a href="/run" target="_blank" rel="noopener noreferrer"><b>visualize</b></a>');
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
        $myTask->disable($_POST['id']);
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
        $myTask->enable($_POST['id']);
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
        $myTask->delete($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task has been deleted');
}

/**
 *  Relaunch a task
 */
if ($_POST['action'] == 'relaunch' and !empty($_POST['id'])) {
    try {
        $myTask->relaunch($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task has been relaunched using the same parameters');
}

/**
 *  Stop a task
 */
if ($_POST['action'] == 'stop' and !empty($_POST['id'])) {
    try {
        $myTask->kill($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task stopped');
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
        $task = $myTask->getById($_POST['taskId']);
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
