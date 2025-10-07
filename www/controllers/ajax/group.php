<?php
if (empty($_POST['type'])) {
    response(HTTP_BAD_REQUEST, 'Group type is required');
}

if (!in_array($_POST['type'], ['host', 'repo'])) {
    response(HTTP_BAD_REQUEST, 'Invalid group type');
}

if ($_POST['type'] == 'repo') {
    $groupController = new \Controllers\Group\Repo();
}

if ($_POST['type'] == 'host') {
    $groupController = new \Controllers\Group\Host();
}

/**
 *  Create a new group
 */
if ($action == 'new' and !empty($_POST['name'])) {
    try {
        $groupController->new($_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Group ' . $_POST['name'] . ' has been created');
}

/**
 *  Delete a group
 */
if ($action == 'delete' and !empty($_POST['id'])) {
    try {
        $groupController->delete($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Group' . (count($_POST['id']) > 1 ? 's' : '') . ' deleted');
}

/**
 *  Edit group
 */
if ($action == 'edit' and !empty($_POST['id']) and !empty($_POST['name'])) {
    $data = [];

    /**
     *  If no data (repo or host) have been specified then it means that the user wants to clean the group, so set $data as an empty array
     */
    if (!empty($_POST['data'])) {
        $data = $_POST['data'];
    }

    try {
        $groupController->edit($_POST['id'], $_POST['name'], $data);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Group ' . $_POST['name'] . ' has been edited');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
