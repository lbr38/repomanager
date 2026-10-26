<?php
/**
 *  Edit hosts settings
 */
if ($action == 'edit' and !empty($_POST['settings'])) {
    $hostController = new \Controllers\Host\Host();

    try {
        $hostController->setSettings($_POST['settings']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Settings updated');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
