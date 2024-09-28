<?php

/**
 *  Edit a repo description
 */
if ($_POST['action'] == "setRepoDescription" and !empty($_POST['envId']) and isset($_POST['description'])) {
    $myrepo = new \Controllers\Repo\Repo();

    try {
        $myrepo->envSetDescription($_POST['envId'], $_POST['description']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Description has been saved");
}

/**
 *  Get repo size
 */
if ($_POST['action'] == 'getRepoSize' and !empty($_POST['path'])) {
    try {
        /**
         *  Check if specified repo path exists
         */
        if (!is_dir(REPOS_DIR . '/' . $_POST['path'])) {
            throw new \Exception('Could not retrieve size of repository ' . $_POST['path']);
        }

        /**
         *  Calculate repo size
         */
        $size = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $_POST['path']);
        $size = \Controllers\Common::sizeFormat($size);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $size);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
