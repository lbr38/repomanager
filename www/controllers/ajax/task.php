<?php
$myTaskForm = new \Controllers\Task\Form\Form();
$myTask = new \Controllers\Task\Task();

/**
 *  Retrieve a task form
 */
if ($_POST['action'] == 'getForm' and !empty($_POST['taskAction']) and !empty($_POST['repos_array'])) {
    try {
        $content = $myTaskForm->get($_POST['taskAction'], json_decode($_POST['repos_array'], true));
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Validate and execute a task form
 */
if ($_POST['action'] == 'validateForm' and !empty($_POST['taskParams'])) {
    $taskParams = json_decode($_POST['taskParams'], true);

    try {
        $myTaskForm->validate($taskParams);
        $myTask->execute($taskParams);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, '<span>Task is running: <a href="/run?view-logfile=latest"><b>visualize</b></a></span>');
}

/**
 *  Disable task execution
 */
if ($_POST['action'] == 'disableTask' and !empty($_POST['taskId'])) {
    try {
        $myTask->disable($_POST['taskId']);
    } catch (\Exception $e) {
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
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task execution enabled');
}

/**
 *  Delete a scheduled task
 */
if ($_POST['action'] == 'deleteTask' and !empty($_POST['taskId'])) {
    try {
        $myTask->delete($_POST['taskId']);
    } catch (\Exception $e) {
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
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task has been relaunched using the same parameters');
}

/**
 *  Stop a task
 */
if ($_POST['action'] == 'stopTask' and !empty($_POST['pid'])) {
    try {
        $myTask->kill($_POST['pid']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task stopped');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
