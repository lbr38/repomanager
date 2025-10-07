<?php
/**
 *  Get repo size
 */
if ($_POST['action'] == 'size' and !empty($_POST['path'])) {
    try {
        /**
         *  Check if specified repo path exists
         */
        if (!is_dir(REPOS_DIR . '/' . $_POST['path'])) {
            throw new Exception('directory not found');
        }

        /**
         *  Calculate repo size
         */
        $size = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $_POST['path']);
        $size = \Controllers\Common::sizeFormat($size);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not retrieve size of repository ' . $_POST['path'] . ': ' . $e->getMessage());
    }

    response(HTTP_OK, $size);
}

/**
 *  Get latest tasks status
 */
if ($_POST['action'] == 'getTaskStatus' and !empty($_POST['repoId']) and !empty($_POST['snapId'])) {
    try {
        // TODO
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not retrieve tasks status: ' . $e->getMessage());
    }

    response(HTTP_OK, $tasks);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
