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

/**
 *  Edit repos list display settings
 */
if (
    $_POST['action'] == "configureReposListDisplay"
    and !empty($_POST['printRepoSize'])
    and !empty($_POST['printRepoType'])
    and !empty($_POST['printRepoSignature'])
    and !empty($_POST['cacheReposList'])
) {
    try {
        \Controllers\Common::configureReposListDisplay($_POST['printRepoSize'], $_POST['printRepoType'], $_POST['printRepoSignature'], $_POST['cacheReposList']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Display settings have been saved");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
