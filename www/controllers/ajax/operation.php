<?php

/**
 *  Retrieve an operation form
 */
if ($_POST['action'] == "getForm" and !empty($_POST['operationAction']) and !empty($_POST['repos_array'])) {
    $operation_action = \Controllers\Common::validateData($_POST['operationAction']);
    $repos_array = json_decode($_POST['repos_array'], true);
    $myop = new \Controllers\Operation();

    try {
        $content = $myop->getForm($operation_action, $repos_array);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Validate and execute an operation form
 */
if ($_POST['action'] == "validateForm" and !empty($_POST['operation_params'])) {
    $operation_params = json_decode($_POST['operation_params'], true);
    $myop = new \Controllers\Operation();

    try {
        $myop->validateForm($operation_params);
        $myop->execute($operation_params);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Operation is running: <a href="/run"><b>visualize</b></a>');
}

/**
 *  Remove a repo snapshot environment
 */
if ($_POST['action'] == "removeEnv" and !empty($_POST['repoId'] and !empty($_POST['snapId']) and !empty($_POST['envId']))) {
    $myrepo = new \Controllers\Repo();
    $myrepo->getAllById(\Controllers\Common::validateData($_POST['repoId']), \Controllers\Common::validateData($_POST['snapId']), \Controllers\Common::validateData($_POST['envId']));

    try {
        $myrepo->removeEnv();
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment has been deleted');
}

/**
 *  Relaunch an operation
 */
if ($_POST['action'] == "relaunchOperation" and !empty($_POST['poolId'])) {
    $myop = new \Controllers\Operation();

    try {
        $myop->executeId($_POST['poolId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Operation has been relaunched using the same parameters.');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
