<?php

use Controllers\Utils\Convert;

/**
 *  Enable or disable debug mode
 */
if ($action == 'enable' and isset($_POST['enable'])) {
    try {
        $settingsController = new \Controllers\Settings();
        $settingsController->enableDebugMode(Convert::toBool($_POST['enable']));
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Settings have been saved');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
