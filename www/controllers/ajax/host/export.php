<?php
use Controllers\User\Permission\Host as HostPermission;

/**
 *  Only users with permissions on hosts can access hosts data
 */
if (!HostPermission::allowed()) {
    response(HTTP_BAD_REQUEST, 'You are not allowed to perform this action');
}

$hostExportController = new \Controllers\Host\Export();

/**
 *  Install host packages
 */
if ($action == 'export' and !empty($_POST['hosts'])) {
    try {
        $content = $hostExportController->export($_POST['hosts']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
