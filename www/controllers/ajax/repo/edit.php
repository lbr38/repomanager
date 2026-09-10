<?php
use \Controllers\Repo\Repo;
use \Controllers\User\Permission\Repo as RepoPermission;

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
