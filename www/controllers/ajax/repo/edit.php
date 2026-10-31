<?php
use \Controllers\Repo\Repo;
use \Controllers\User\Permission\Repo as RepoPermission;

/**
 *  Edit mirror description or tags
 *  Mirrors are listed with repositories and use the same edit fields, their Id is prefixed with 'mirror-'
 */
if (in_array($_POST['action'], ['description', 'tags']) and !empty($_POST['repoId']) and str_starts_with($_POST['repoId'], 'mirror-')) {
    $mirrorController = new \Controllers\Mirror\Mirror();
    $mirrorId = (int) substr($_POST['repoId'], strlen('mirror-'));

    try {
        if (!RepoPermission::allowedAction('edit')) {
            throw new Exception('You do not have permission to edit repositories');
        }

        if (empty($mirrorController->getById($mirrorId)) or !RepoPermission::allowedToViewMirror($mirrorId)) {
            throw new Exception('You do not have permission to edit this mirror');
        }

        if ($_POST['action'] == 'description' and isset($_POST['description'])) {
            $mirrorController->updateDescription($mirrorId, $_POST['description']);
            response(HTTP_OK, 'Description has been saved');
        }

        if ($_POST['action'] == 'tags' and isset($_POST['tags'])) {
            // Tags are sent as a comma-separated string
            $mirrorController->updateTags($mirrorId, explode(',', $_POST['tags']));
            response(HTTP_OK, 'Tags have been saved');
        }
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }
}

/**
 *  Edit repository description
 */
if ($_POST['action'] == 'description' and !empty($_POST['repoId']) and isset($_POST['description'])) {
    $repoController = new Repo();

    try {
        if (!RepoPermission::allowedAction('edit')) {
            throw new Exception('You do not have permission to edit repositories');
        }

        if (!RepoPermission::allowedToView($_POST['repoId'])) {
            throw new Exception('You do not have permission to edit this repository');
        }

        $repoController->updateDescription($_POST['repoId'], $_POST['description']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Description has been saved');
}

/**
 *  Edit repository tags
 */
if ($_POST['action'] == 'tags' and !empty($_POST['repoId']) and isset($_POST['tags'])) {
    $repoController = new Repo();

    // Tags are sent as a comma-separated string
    $tags = array_filter(array_map('trim', explode(',', $_POST['tags'])), function ($tag) {
        return $tag !== '';
    });

    try {
        if (!RepoPermission::allowedAction('edit')) {
            throw new Exception('You do not have permission to edit repositories');
        }

        if (!RepoPermission::allowedToView($_POST['repoId'])) {
            throw new Exception('You do not have permission to edit this repository');
        }

        $repoController->updateTags($_POST['repoId'], $tags);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Tags have been saved');
}

/**
 *  Validate form and edit repositories
 */
if ($_POST['action'] == 'validate-execute' and !empty($_POST['params'])) {
    $repoEditForm = new \Controllers\Repo\Edit\Form();

    try {
        try {
            $params = json_decode($_POST['params'], true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new Exception('Could not decode form parameters: ' . $e->getMessage());
        }

        $repoEditForm->validate($params);
        $repoEditForm->edit($params);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Successfully edited.');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
