<?php
use \Controllers\Task\Task;

/**
 *  Get latest tasks status
 */
if ($_POST['action'] == 'latest-task-status' and !empty($_POST['snapId'])) {
    try {
        $taskController = new Task();
        $status = $taskController->getLatestStatus($_POST['snapId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not retrieve latest task status for snapshot #' . $_POST['snapId'] . ': ' . $e->getMessage());
    }

    response(HTTP_OK, $status);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
