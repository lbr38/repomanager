<?php

/**
 *  Retrieve an operation form
 */
if ($_POST['action'] == "getForm" and !empty($_POST['operationAction']) and !empty($_POST['repos_array'])) {
    $action = \Controllers\Common::validateData($_POST['operationAction']);
    $repos = json_decode($_POST['repos_array'], true);

    $myOperationForm = new \Controllers\Operation\Form();

    try {
        $content = $myOperationForm->getForm($action, $repos);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Validate and execute an operation form
 */
if ($_POST['action'] == "validateForm" and !empty($_POST['operation_params'])) {
    $operationParams = json_decode($_POST['operation_params'], true);

    $myOperationForm = new \Controllers\Operation\Form();
    $myoperation = new \Controllers\Operation\Operation();

    try {
        $myOperationForm->validateForm($operationParams);
        $myoperation->execute($operationParams);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, '<span>Operation is running: <a href="/run?view-logfile=latest"><b>visualize</b></a></span>');
}

/**
 *  Remove a repo snapshot environment
 */
if ($_POST['action'] == "removeEnv" and !empty($_POST['repoId'] and !empty($_POST['snapId']) and !empty($_POST['envId']))) {
    $operationParams['repoId'] = $_POST['repoId'];
    $operationParams['snapId'] = $_POST['snapId'];
    $operationParams['envId'] = $_POST['envId'];

    try {
        $controller = new \Controllers\Repo\Operation\RemoveEnv('00000', $operationParams);
        $controller->execute();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment has been removed');
}

/**
 *  Relaunch an operation
 */
if ($_POST['action'] == "relaunchOperation" and !empty($_POST['poolId'])) {
    $myop = new \Controllers\Operation\Operation();

    try {
        $myop->executeId($_POST['poolId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Operation has been relaunched using the same parameters');
}

/**
 *  Relaunch an operation
 */
if ($_POST['action'] == "stopOperation" and !empty($_POST['pid'])) {
    $myop = new \Controllers\Operation\Operation();

    try {
        $myop->kill($_POST['pid']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Operation stopped');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
