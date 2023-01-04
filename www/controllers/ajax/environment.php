<?php

/**
 *  Delete an environment
 */
if ($action == 'deleteEnv' and !empty($_POST['name'])) {
    $myenv = new \Controllers\Environment();

    try {
        $myenv->delete($_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment <b>' . $_POST['name'] . '</b> has been deleted');
}

/**
 *  Add / edit actual envs
 */
if ($action == 'editEnv' and !empty($_POST['envs'])) {
    $myenv = new \Controllers\Environment();

    try {
        $myenv->edit($_POST['envs']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment changes taken into account');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
