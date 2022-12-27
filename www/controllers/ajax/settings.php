<?php

/**
 *  Apply settings
 */
if ($action == "applySettings" and !empty($_POST['settings_params'])) {
    $mysettings = new \Controllers\Settings();

    try {
        $mysettings->apply(json_decode($_POST['settings_params'], true));
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Settings have been saved');
}

/**
 *  Create a new user
 */
if ($action == "createUser" and !empty($_POST['username']) and !empty($_POST['role'])) {
    $mylogin = new \Controllers\Login();

    try {
        $generatedPassword = $mylogin->addUser($_POST['username'], $_POST['role']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, array('message' => 'User <b>' . $_POST['username'] . '</b> has been created', 'password' => $generatedPassword));
}

/**
 *  Reset user password
 */
if ($action == "resetPassword" and !empty($_POST['id'])) {
    $mylogin = new \Controllers\Login();

    try {
        $generatedPassword = $mylogin->resetPassword($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, array('message' => 'Password has been regenerated', 'password' => $generatedPassword));
}

/**
 *  Delete user
 */
if ($action == "deleteUser" and !empty($_POST['id'])) {
    $mylogin = new \Controllers\Login();

    try {
        $mylogin->deleteUser($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'User has been deleted');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
