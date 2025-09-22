<?php

/**
 *  Get and return the log content of a service unit
 */
if ($action == 'get-unit-log' and !empty($_POST['unit']) and !empty($_POST['logfile'])) {
    $logfile = \Controllers\Common::validateData($_POST['logfile']);

    // Load service units configuration
    include_once(ROOT . '/config/service-units.php');

    // Check if the unit exists
    if (!isset($units[$_POST['unit']])) {
        response(HTTP_BAD_REQUEST, 'Invalid unit');
    }

    // Get the log directory for the unit
    $logDir = $units[$_POST['unit']]['log-dir'] ?? $_POST['unit'];

    // Define the full path to the log file
    $logfile = SERVICE_LOGS_DIR . '/' . $logDir . '/' . $logfile;

    // Check if the logfile exists
    if (!file_exists($logfile)) {
        response(HTTP_BAD_REQUEST, 'Log file not found');
    }

    // Get the log content
    $content = file_get_contents($logfile);

    // Check if the log content was read successfully
    if ($content === false) {
        response(HTTP_BAD_REQUEST, 'Unable to read log file');
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
