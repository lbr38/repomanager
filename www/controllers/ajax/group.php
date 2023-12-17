<?php

/**
 *  Create a new group
 */
if ($action == "new" and !empty($_POST['name']) and !empty($_POST['type'])) {
    $mygroup = new \Controllers\Group($_POST['type']);

    try {
        $mygroup->new($_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Group <b>" . $_POST['name'] . "</b> has been created");
}

/**
 *  Rename a group
 */
if ($action == "rename" and !empty($_POST['name']) and !empty($_POST['newname']) and !empty($_POST['type'])) {
    $mygroup = new \Controllers\Group($_POST['type']);

    try {
        $mygroup->rename($_POST['name'], $_POST['newname']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Group <b>" . $_POST['name'] . "</b> has been renamed to <b>" . $_POST['newname'] . "</b>");
}

/**
 *  Delete a group
 */
if ($action == "delete" and !empty($_POST['id']) and !empty($_POST['type'])) {
    $mygroup = new \Controllers\Group($_POST['type']);

    try {
        $mygroup->delete($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Group deleted');
}

/**
 *  Edit group
 */
if ($action == "edit" and !empty($_POST['id']) and !empty($_POST['name']) and !empty($_POST['type'])) {
    $data = array();

    /**
     *  If no data (repo or host) have been specified then it means that the user wants to clean the group, so set $data as an empty array
     */
    if (!empty($_POST['data'])) {
        $data = $_POST['data'];
    }

    $mygroup = new \Controllers\Group($_POST['type']);

    try {
        $mygroup->edit($_POST['id'], $_POST['name'], $data);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Group <b>" . $_POST['name'] . "</b> has been edited");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
