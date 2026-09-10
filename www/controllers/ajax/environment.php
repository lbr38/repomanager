<?php
$envController = new \Controllers\Environment();

/**
 *  Add a new environment
 */
if ($action == 'add' and !empty($_POST['name']) and !empty($_POST['color'])) {
    try {
        $envController->add($_POST['name'], $_POST['color']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment ' . $_POST['name'] . ' added');
}

/**
 *  Delete an environment
 */
if ($action == 'delete' and !empty($_POST['id'])) {
    try {
        $envController->delete($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment deleted');
}

/**
 *  Edit environment(s)
 */
if ($action == 'edit' and !empty($_POST['envs'])) {
    try {
        $envController->edit($_POST['envs']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Environment' . (count($_POST['envs']) > 1 ? 's' : '') . ' updated');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
