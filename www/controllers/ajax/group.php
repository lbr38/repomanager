<?php

/**
 *  Create a new group
 */
if ($action == "newGroup" and !empty($_POST['name']) and !empty($_POST['type'])) {
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
if ($action == "renameGroup" and !empty($_POST['name']) and !empty($_POST['newname']) and !empty($_POST['type'])) {
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
if ($action == "deleteGroup" and !empty($_POST['name']) and !empty($_POST['type'])) {
    $mygroup = new \Controllers\Group($_POST['type']);

    try {
        $mygroup->delete($_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Group <b>" . $_POST['name'] . "</b> has been deleted");
}

/**
 *  Add / remove repo(s) from a group
 */
if ($action == "editGroupRepos" and !empty($_POST['name'])) {
    /**
     *  If no repos have been specified then it means that the user wants to clean the group, so set $reposId as an empty array
     */
    if (empty($_POST['reposId'])) {
        $reposId = array();
    } else {
        $reposId = $_POST['reposId'];
    }

    $myrepo = new \Controllers\Repo\Repo('repo');

    try {
        $myrepo->addReposIdToGroup($reposId, $_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Group <b>" . $_POST['name'] . "</b> has been edited");
}

/**
 *  Add / remove host(s) from a group
 */
if ($action == "editGroupHosts" and !empty($_POST['name'])) {
    /**
     *  If no hosts have been specified then it means that the user wants to clean the group, so set $hostsId as an empty array
     */
    if (empty($_POST['hostsId'])) {
        $hostsId = array();
    } else {
        $hostsId = $_POST['hostsId'];
    }

    $myhost = new \Controllers\Host();

    try {
        $myhost->addHostsIdToGroup($hostsId, $_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Group <b>" . $_POST['name'] . "</b> has been edited");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
