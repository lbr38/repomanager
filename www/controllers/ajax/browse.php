<?php
/**
 *  Delete packages from repo
 */
if ($action == 'deletePackage' and !empty($_POST['snapId']) and !empty($_POST['packages'])) {
    $myrepoPackage = new \Controllers\Repo\Package();

    try {
        $deletedPackages = $myrepoPackage->delete($_POST['snapId'], $_POST['packages']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Packages deleted: <br>' . implode('<br>', $deletedPackages));
}

/**
 *  Rebuild repo metadata files
 */
if ($action == 'rebuild' and !empty($_POST['snapId']) and !empty($_POST['rebuildGpgSign'])) {
    $myrepo = new \Controllers\Repo\Repo();
    $myoperation = new \Controllers\Operation\Operation();

    try {
        if ($myrepo->existsSnapId($_POST['snapId']) !== true) {
            throw new Exception('Invalid repo snapshot ID');
        }

        if ($_POST['rebuildGpgSign'] != 'yes' and $_POST['rebuildGpgSign'] != 'no') {
            throw new Exception('Invalid GPG Resign value');
        }

        /**
         *  Create a json file that defines the operation to execute
         */
        $params = array();
        $params['action'] = 'rebuild';
        $params['snapId'] = $_POST['snapId'];
        $params['targetGpgResign'] = $_POST['rebuildGpgSign'];

        /**
         *  Execute the operation
         */
        $myoperation->execute(array($params));
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Repository rebuilding started');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
