<?php

/**
 *  Send a test email
 */
if ($action == "sendTestEmail") {
    try {
        new \Controllers\Mail(implode(',', EMAIL_RECIPIENT), 'Test email', 'This is a test email sent by Repomanager.');
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

response(HTTP_BAD_REQUEST, 'Invalid action');
