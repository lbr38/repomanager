<?php
/**
 *  Delete packages from repo
 */
if ($action == 'deletePackage' and !empty($_POST['snapId']) and !empty($_POST['packages'])) {
    $myrepoPackage = new \Controllers\Repo\Package();

    try {
        $deletedPackages = $myrepoPackage->delete($_POST['snapId'], $_POST['packages']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Packages deleted: <br>' . implode('<br>', $deletedPackages));
}

/**
 *  Rebuild repo metadata files
 */
if ($action == 'rebuild' and !empty($_POST['snapId']) and !empty($_POST['gpgSign'])) {
    $myrepo = new \Controllers\Repo\Repo();
    $mytask = new \Controllers\Task\Task();

    try {
        if ($myrepo->existsSnapId($_POST['snapId']) !== true) {
            throw new Exception('Invalid repository snapshot Id');
        }

        if ($_POST['gpgSign'] != 'true' and $_POST['gpgSign'] != 'false') {
            throw new Exception('Invalid GPG sign value');
        }

        /**
         *  Create a json file that defines the task to execute
         */
        $params = [];
        $params['action'] = 'rebuild';
        $params['snap-id'] = $_POST['snapId'];
        $params['gpg-sign'] = $_POST['gpgSign'];
        $params['schedule']['scheduled'] = 'false';

        /**
         *  Execute the task
         */
        $mytask->execute(array($params));
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Repository rebuilding started');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
