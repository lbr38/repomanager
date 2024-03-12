<?php

/**
 *  Retrieve a task form
 */
if ($_POST['action'] == 'getForm' and !empty($_POST['taskAction']) and !empty($_POST['repos_array'])) {
    $myTaskForm = new \Controllers\Task\Form\Form();

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

    $myTaskForm = new \Controllers\Task\Form\Form();
    $myTask = new \Controllers\Task\Task();

    try {
        $myTaskForm->validate($taskParams);
        $myTask->execute($taskParams);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, '<span>Task is running: <a href="/run?view-logfile=latest"><b>visualize</b></a></span>');
}

/**
 *  Remove a repo snapshot environment
 */
if ($_POST['action'] == 'removeEnv' and !empty($_POST['repoId'] and !empty($_POST['snapId']) and !empty($_POST['envId']))) {
    $taskParams['repoId'] = $_POST['repoId'];
    $taskParams['snapId'] = $_POST['snapId'];
    $taskParams['envId']  = $_POST['envId'];

    try {
        $controller = new \Controllers\Repo\Operation\RemoveEnv('00000', $taskParams);
        $controller->execute();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment has been removed');
}

/**
 *  Relaunch a task
 */
if ($_POST['action'] == 'relaunchTask' and !empty($_POST['poolId'])) {
    $myTask = new \Controllers\Task\Task();

    try {
        $myTask->executeId($_POST['poolId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task has been relaunched using the same parameters');
}

/**
 *  Stop a task
 */
if ($_POST['action'] == "stopTask" and !empty($_POST['pid'])) {
    $myTask = new \Controllers\Task\Task();

    try {
        $myTask->kill($_POST['pid']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Task stopped');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
