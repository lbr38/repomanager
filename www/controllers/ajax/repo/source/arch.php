<?php
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  Only users allowed to edit source repositories can modify them
 *  Predefined values can be retrieved by any user, they are used in tasks forms
 */
if (!str_starts_with($action, 'get-predefined') and !RepoPermission::allowedAction('edit-source')) {
    response(HTTP_BAD_REQUEST, 'You are not allowed to perform this action');
}

use \Controllers\Repo\Source\Items as ItemsSourceRepo;

/**
 *  Items (repositories, architectures, branches, versions) of the arch, alpine and openbsd source repositories
 */

/**
 *  Add an item
 */
if ($_POST['action'] == 'add' and !empty($_POST['id']) and !empty($_POST['field']) and isset($_POST['name'])) {
    try {
        $sourceController = ItemsSourceRepo::fromId((int) $_POST['id']);
        $sourceController->addItem($_POST['field'], $_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $sourceController::getItemLabel($_POST['field']) . ' added');
}

/**
 *  Edit an item
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and !empty($_POST['field']) and isset($_POST['itemId']) and is_numeric($_POST['itemId']) and isset($_POST['name']) and isset($_POST['description'])) {
    try {
        $sourceController = ItemsSourceRepo::fromId((int) $_POST['id']);
        $sourceController->editItem($_POST['field'], (int) $_POST['itemId'], $_POST['name'], $_POST['description'], $_POST['eol'] ?? '');
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $sourceController::getItemLabel($_POST['field']) . ' edited');
}

/**
 *  Remove an item
 */
if ($_POST['action'] == 'remove' and !empty($_POST['id']) and !empty($_POST['field']) and isset($_POST['itemId']) and is_numeric($_POST['itemId'])) {
    try {
        $sourceController = ItemsSourceRepo::fromId((int) $_POST['id']);
        $sourceController->removeItem($_POST['field'], (int) $_POST['itemId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $sourceController::getItemLabel($_POST['field']) . ' removed');
}

/**
 *  Get predefined items for a task (source repositories names are unique per package type, arch by default)
 */
if ($_POST['action'] == 'get-predefined' and !empty($_POST['field']) and !empty($_POST['source'])) {
    try {
        $content = ItemsSourceRepo::fromType($_POST['type'] ?? 'arch')->getPredefined($_POST['field'], $_POST['source']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
