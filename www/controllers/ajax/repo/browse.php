<?php
if ($action == 'view-file' and !empty($_POST['path'])) {
    try {
        /**
         *  Check that the file path starts with REPOS_DIR
         *  Prevents a malicious person from providing a path that has nothing to do with the repo directory (e.g. /etc/...)
         */
        if (!preg_match("#^" . REPOS_DIR . "#", realpath(REPOS_DIR . '/' . $_POST['path']))) {
            throw new Exception('invalid path ' . REPOS_DIR . '/' . $_POST['path']);
        }

        $content = file_get_contents(REPOS_DIR . '/' . $_POST['path']);

        if ($content === false) {
            throw new Exception('could not read file ' . REPOS_DIR . '/' . $_POST['path']);
        }
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Return the repository tree structure
 */
if ($_POST['action'] == 'tree' and !empty($_POST['path'])) {
    try {
        /**
         *  Check that the file path starts with REPOS_DIR
         *  Prevents a malicious person from providing a path that has nothing to do with the repo directory (e.g. /etc/...)
         */
        if (!preg_match("#^" . REPOS_DIR . "#", realpath($_POST['path']))) {
            throw new Exception('invalid path ' . $_POST['path']);
        }

        $result = \Controllers\Repo\Browse::render($_POST['path']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not generate repository tree: ' . $e->getMessage());
    }

    response(HTTP_OK, $result);
}

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
