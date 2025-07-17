<?php
/**
 *  Create a new user
 */
if ($action == 'create' and !empty($_POST['username']) and !empty($_POST['role'])) {
    try {
        $userCreateController = new \Controllers\User\Create();
        $generatedPassword = $userCreateController->create($_POST['username'], $_POST['role']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, array('message' => 'User ' . $_POST['username'] . ' has been created', 'password' => $generatedPassword));
}

/**
 *  Reset user password
 */
if ($action == 'reset-password' and !empty($_POST['id'])) {
    try {
        $userEditController = new \Controllers\User\Edit();
        $generatedPassword = $userEditController->resetPassword($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, array('message' => 'Password has been regenerated', 'password' => $generatedPassword));
}

/**
 *  Delete user
 */
if ($action == 'delete' and !empty($_POST['id'])) {
    try {
        $userDeleteController = new \Controllers\User\Delete();
        $userDeleteController->delete($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'User has been deleted');
}

/**
 *  Edit user permissions
 */
if ($action == 'edit-permissions' and !empty($_POST['id']) and isset($_POST['reposView']) and isset($_POST['reposActions']) and isset($_POST['tasksActions'])) {
    try {
        $userPermissionController = new \Controllers\User\Permission();
        $userPermissionController->set($_POST['id'], $_POST['reposView'], $_POST['reposActions'], $_POST['tasksActions']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'User permissions have been updated');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
