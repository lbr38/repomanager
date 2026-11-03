<?php
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  Only users allowed to edit source repositories can modify them
 *  Predefined values can be retrieved by any user, they are used in tasks forms
 */
if (!str_starts_with($action, 'get-predefined') and !RepoPermission::allowedAction('edit-source')) {
    response(HTTP_BAD_REQUEST, 'You are not allowed to perform this action');
}

use \Controllers\Repo\Source\Arch as ArchSourceRepo;

/**
 *  Add a repository or an architecture
 */
if ($_POST['action'] == 'add' and !empty($_POST['id']) and !empty($_POST['field']) and !empty($_POST['name'])) {
    try {
        $archSourceController = new ArchSourceRepo($_POST['id']);
        $archSourceController->addItem($_POST['field'], $_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, ($_POST['field'] == 'archs' ? 'Architecture' : 'Repository') . ' added');
}

/**
 *  Remove a repository or an architecture
 */
if ($_POST['action'] == 'remove' and !empty($_POST['id']) and !empty($_POST['field']) and isset($_POST['itemId']) and is_numeric($_POST['itemId'])) {
    try {
        $archSourceController = new ArchSourceRepo($_POST['id']);
        $archSourceController->removeItem($_POST['field'], (int) $_POST['itemId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, ($_POST['field'] == 'archs' ? 'Architecture' : 'Repository') . ' removed');
}

/**
 *  Get predefined repositories or architectures values for a task
 */
if ($_POST['action'] == 'get-predefined' and !empty($_POST['field']) and !empty($_POST['source'])) {
    try {
        $content = (new ArchSourceRepo())->getPredefined($_POST['field'], $_POST['source']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
