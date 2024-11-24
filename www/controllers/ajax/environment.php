<?php
/**
 *  Add a new environment
 */
if ($action == 'add-env' and !empty($_POST['name']) and !empty($_POST['color'])) {
    $myenv = new \Controllers\Environment();

    try {
        $myenv->add($_POST['name'], $_POST['color']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment <b>' . $_POST['name'] . '</b> has been added');
}

/**
 *  Delete an environment
 */
if ($action == 'delete-env' and !empty($_POST['id'])) {
    $myenv = new \Controllers\Environment();

    try {
        $myenv->delete($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment deleted');
}

/**
 *  Add / edit actual envs
 */
if ($action == 'edit-env' and !empty($_POST['envs'])) {
    $myenv = new \Controllers\Environment();

    try {
        $myenv->edit($_POST['envs']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environments edited');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
