<?php
/**
 *  Get event details
 */
if ($action == 'get-details' and !empty($_POST['hostId']) and !empty($_POST['id'])) {
    $hostEventController = new \Controllers\Host\Package\Event($_POST['hostId']);

    try {
        $content = $hostEventController->generateDetails($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get event packages details (installed packages, updated packages...)
 */
if ($action == 'get-packages-details' and !empty($_POST['hostId']) and !empty($_POST['date'])) {
    $hostPackageController = new \Controllers\Host\Package\Package($_POST['hostId']);

    try {
        $content = $hostPackageController->generateDetails($_POST['date']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
