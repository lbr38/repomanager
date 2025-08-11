<?php
$hostExportController = new \Controllers\Host\Export();

/**
 *  Install host packages
 */
if ($action == 'export' and !empty($_POST['hosts'])) {
    try {
        $content = $hostExportController->export($_POST['hosts']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
