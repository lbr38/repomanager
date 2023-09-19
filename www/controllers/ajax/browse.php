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
 *  Reconstruct repo metadata files
 */
if ($action == 'reconstruct' and !empty($_POST['snapId']) and !empty($_POST['reconstructGpgSign'])) {
    $myrepo = new \Controllers\Repo\Repo();
    $myoperation = new \Controllers\Operation\Operation();

    try {
        if ($myrepo->existsSnapId($_POST['snapId']) !== true) {
            throw new Exception('Invalid repo snapshot ID');
        }

        if ($_POST['reconstructGpgSign'] != 'yes' and $_POST['reconstructGpgSign'] != 'no') {
            throw new Exception('Invalid GPG Resign value');
        }

        /**
         *  Create a json file that defines the operation to execute
         */
        $params = array();
        $params['action'] = 'reconstruct';
        $params['snapId'] = $_POST['snapId'];
        $params['targetGpgResign'] = $_POST['reconstructGpgSign'];

        /**
         *  Execute the operation
         */
        $myoperation->execute(array($params));
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Repo reconstruction started');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
