<?php
$myTask = new \Controllers\Task\Task();

/**
 *  Validate and execute a task form
 */
if ($_POST['action'] == 'validateForm' and !empty($_POST['taskParams'])) {
    $myTaskForm = new \Controllers\Task\Form\Form();
    $taskRawParams = json_decode($_POST['taskParams'], true);

    try {
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
 *  Disable task execution
 */
if ($_POST['action'] == 'disableTask' and !empty($_POST['taskId'])) {
    try {
        $myTask->disable($_POST['taskId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task execution disabled');
}

/**
 *  Enable task execution
 */
if ($_POST['action'] == 'enableTask' and !empty($_POST['taskId'])) {
    try {
        $myTask->enable($_POST['taskId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task execution enabled');
}

/**
 *  Delete a scheduled task
 */
if ($_POST['action'] == 'deleteTask' and !empty($_POST['id'])) {
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
if ($_POST['action'] == 'relaunchTask' and !empty($_POST['taskId'])) {
    try {
        $myTask->relaunch($_POST['taskId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task has been relaunched using the same parameters');
}

/**
 *  Stop a task
 */
if ($_POST['action'] == 'stopTask' and !empty($_POST['taskId'])) {
    try {
        $myTask->kill($_POST['taskId']);
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

if ($_POST['action'] == 'get-task-status'  and !empty($_POST['taskId'])) {
    try {
        $task = $myTask->getById($_POST['taskId']);
        $status = $task['Status'];
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $status);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
