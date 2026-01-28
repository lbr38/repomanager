<?php
use \Controllers\Filesystem\Directory;
use \Controllers\Utils\Convert;
use \Controllers\Task\Task;

/**
 *  Get repo size
 */
if ($_POST['action'] == 'size' and !empty($_POST['path'])) {
    try {
        // Check if specified repo path exists
        if (!is_dir(REPOS_DIR . '/' . $_POST['path'])) {
            throw new Exception('directory not found');
        }

        // Calculate repo size
        $size = Convert::sizeToHuman(Directory::getSize(REPOS_DIR . '/' . $_POST['path']));
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not retrieve size of repository ' . $_POST['path'] . ': ' . $e->getMessage());
    }

    response(HTTP_OK, $size);
}

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
