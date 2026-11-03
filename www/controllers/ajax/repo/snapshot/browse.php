<?php
use Controllers\Task\Form\Form as TaskForm;

if ($action == 'view-file' and !empty($_POST['path'])) {
    try {
        // File path is relative to the repositories directory, or to the mirrors directory when browsing a mirror snapshot
        $rootDir = ($_POST['root'] ?? '') == 'mirror' ? MIRRORS_DIR : REPOS_DIR;
        $file = $rootDir . '/' . $_POST['path'];

        /**
         *  Check that the file path is inside the repositories or mirrors directory
         *  Prevents a malicious person from providing a path that has nothing to do with the repo directory (e.g. /etc/...)
         */
        if (!\Controllers\Repo\Browse::allowedPath($file)) {
            throw new Exception('invalid path ' . $file);
        }

        $content = file_get_contents($file);

        if ($content === false) {
            throw new Exception('could not read file ' . $file);
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
        // Check that the path is inside the repositories or mirrors directory
        if (!\Controllers\Repo\Browse::allowedPath($_POST['path'])) {
            throw new Exception('invalid path ' . $_POST['path']);
        }

        $result = \Controllers\Repo\Browse::render($_POST['path']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not generate repository tree: ' . $e->getMessage());
    }

    response(HTTP_OK, $result);
}

/**
 *  Return the next page of files for a directory (load more)
 */
if ($_POST['action'] == 'tree/page' and !empty($_POST['path']) and isset($_POST['offset'])) {
    try {
        if (!\Controllers\Repo\Browse::allowedPath($_POST['path'])) {
            throw new Exception('invalid path ' . $_POST['path']);
        }

        $offset = max(0, (int) $_POST['offset']);
        $result = \Controllers\Repo\Browse::renderPage($_POST['path'], $offset);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not generate repository tree: ' . $e->getMessage());
    }

    response(HTTP_OK, $result);
}

/**
 *  Search for files matching a query string across the entire repository subtree
 */
if ($_POST['action'] == 'tree/search' and !empty($_POST['path']) and isset($_POST['query'])) {
    try {
        if (!\Controllers\Repo\Browse::allowedPath($_POST['path'])) {
            throw new Exception('invalid path ' . $_POST['path']);
        }

        $query = trim($_POST['query']);

        if (strlen($query) < 2) {
            throw new Exception('search query must be at least 2 characters');
        }

        $result = \Controllers\Repo\Browse::search($_POST['path'], $query);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, 'Could not search repository: ' . $e->getMessage());
    }

    response(HTTP_OK, $result);
}

/**
 *  Delete packages from a snapshot
 */
if ($action == 'delete-package' and !empty($_POST['snapId']) and !empty($_POST['packages'])) {
    $repoSnapshotPackageController = new \Controllers\Repo\Snapshot\Package($_POST['snapId']);

    try {
        $deleted = $repoSnapshotPackageController->delete($_POST['packages']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $deleted);
}

/**
 *  Upload packages to a snapshot
 */
if ($action == 'upload-package' and !empty($_POST['snapId']) and is_numeric($_POST['snapId']) and !empty($_FILES['packages'])) {
    $repoSnapshotPackageController = new \Controllers\Repo\Snapshot\Package($_POST['snapId']);

    try {
        // Validate the overwrite value if it has been provided
        if (isset($_POST['overwrite']) and is_null($overwrite = \Controllers\Utils\Convert::toBool($_POST['overwrite']))) {
            throw new Exception('Invalid overwrite value');
        }

        $uploaded = $repoSnapshotPackageController->upload(\Controllers\Utils\Array\Sort::byPostFiles($_FILES['packages']), $overwrite ?? false);
    } catch (\Controllers\Exception\AppException $e) {
        response(HTTP_BAD_REQUEST, $e->getDetails());
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $uploaded);
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
         *  Validate the task like any other task form (user permission, protected environment...)
         */
        $taskFormController = new TaskForm();
        $taskFormController->validate(array($params));

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
