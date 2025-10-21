<?php
/**
 *  Get event details (installed packages, updated packages...)
 */
if ($action == 'get-packages-details' and !empty($_POST['hostId']) and !empty($_POST['date']) and !empty($_POST['state'])) {
    $hostPackageController = new \Controllers\Host\Package\Package($_POST['hostId']);

    try {
        $content = $hostPackageController->generateDetails($_POST['date'], $_POST['state']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
