<?php

/**
 *  Send a test email
 */
if ($action == "sendTestEmail") {
    try {
        $mymail = new \Controllers\Mail(implode(',', EMAIL_RECIPIENT), 'Test email', 'This is a test email sent by Repomanager.');
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Email sent');
}

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

/**
 *  Get websocker server log content
 */
if ($action == 'get-wss-log' and !empty([$_POST['logfile']])) {
    $logfile = \Controllers\Common::validateData($_POST['logfile']);

    // Check if the log file is allowed and is not outside the logs directory. Verify that the user is not trying to do something malicious.
    if (!preg_match('#^' . WS_LOGS_DIR . '#', realpath(WS_LOGS_DIR . '/' . $logfile))) {
        response(HTTP_BAD_REQUEST, 'Invalid log file');
    }

    // Check if the log file exists
    if (!file_exists(WS_LOGS_DIR . '/' . $logfile)) {
        response(HTTP_BAD_REQUEST, 'Log file not found');
    }

    // Get the log content
    $content = file_get_contents(WS_LOGS_DIR . '/' . $logfile);

    // Check if the log content was read successfully
    if ($content === false) {
        response(HTTP_BAD_REQUEST, 'Unable to read log file');
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
