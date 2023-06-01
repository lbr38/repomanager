<?php

/**
 *  Edit a repo description
 */
if ($_POST['action'] == "setRepoDescription" and !empty($_POST['envId']) and isset($_POST['description'])) {
    $myrepo = new \Controllers\Repo();

    try {
        $myrepo->envSetDescription($_POST['envId'], $_POST['description']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Description has been saved");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
