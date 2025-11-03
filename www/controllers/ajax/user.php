<?php

/**
 *  Generate new API key
 */
if ($_POST['action'] == 'generateApikey' and !empty($_SESSION['username'])) {
    try {
        $userController = new \Controllers\User\User();
        $apiKey = $userController->generateApiKey();
        $userController->updateApiKey($_SESSION['username'], $_SESSION['type'], $apiKey);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    /**
     *  Send back API key to javascript to print it to the user
     */
    response(HTTP_OK, $apiKey);
}

/**
 *  Edit user personnal informations
 */
if ($_POST['action'] == 'edit' and isset($_POST['firstName']) and isset($_POST['lastName']) and isset($_POST['email'])) {
    try {
        $userEditController = new \Controllers\User\Edit();
        $userEditController->edit($_SESSION['id'], $_SESSION['type'], $_POST['firstName'], $_POST['lastName'], $_POST['email']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Personnal informations saved');
}

/**
 *  Change password
 */
if ($_POST['action'] == 'changePassword' and !empty($_POST['actualPassword']) and !empty($_POST['newPassword']) and !empty($_POST['newPasswordConfirm'])) {
    try {
        $userEditController = new \Controllers\User\Edit();
        $userEditController->changePassword($_SESSION['id'], $_SESSION['type'], $_POST['actualPassword'], $_POST['newPassword'], $_POST['newPasswordConfirm']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'New password saved');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
