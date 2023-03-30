<?php

/**
 *  Edit server settings
 */
if (
    $_POST['action'] == "applyServerConfiguration"
    and !empty($_POST['serverManageClientConf'])
    and !empty($_POST['serverManageClientRepos'])
) {
    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->setServerConfiguration($_POST['serverManageClientConf'], $_POST['serverManageClientRepos']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Server configuration has been saved");
}

/*
 *  Create a new profile
 */
if ($_POST['action'] == "newProfile" and !empty($_POST['name'])) {
    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->new($_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "<b>" . $_POST['name'] . "</b> profile has been created");
}

/**
 *  Delete a profile
 */
if ($_POST['action'] == "deleteProfile" and !empty($_POST['name'])) {
    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->delete($_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "<b>" . $_POST['name'] . "</b> profile has been deleted");
}

/**
 *  Rename a profile
 */
if ($_POST['action'] == "renameProfile" and !empty($_POST['name']) and !empty($_POST['newname'])) {
    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->rename($_POST['name'], $_POST['newname']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "<b>" . $_POST['name'] . "</b> profile has been renamed to <b>" . $_POST['newname'] . "</b>");
}

/**
 *  Duplicate a profile
 */
if ($_POST['action'] == "duplicateProfile" and !empty($_POST['name'])) {
    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->duplicate($_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "<b>" . $_POST['name'] . "</b> profile has been duplicated");
}

/**
 *  Configure a profile
 */
if (
    $_POST['action'] == "configureProfile"
    and !empty($_POST['name'])
    and !empty($_POST['linupdateGetPkgConf'])
    and !empty($_POST['linupdateGetReposConf'])
) {
    /**
     *  If no repos have been specified then it means that the user wants to set it clear, so set $reposList as en empty array
     */
    if (empty($_POST['reposList'])) {
        $reposList = array();
    } else {
        $reposList = $_POST['reposList'];
    }

    /**
     *  If no package to exclude have been specified then it means that the user wants to set it clear, so set $packagesExcluded as en empty array
     */
    if (empty($_POST['packagesExcluded'])) {
        $packagesExcluded = array();
    } else {
        $packagesExcluded = $_POST['packagesExcluded'];
    }

    /**
     *  If no package to exclude (on major version) have been specified then it means that the user wants to set it clear, so set $packagesMajorExcluded as en empty array
     */
    if (empty($_POST['packagesMajorExcluded'])) {
        $packagesMajorExcluded = array();
    } else {
        $packagesMajorExcluded = $_POST['packagesMajorExcluded'];
    }

    /**
     *  If no service to restart have been specified then it means that the user wants to set it clear, so set $packagesExcluded as en empty array
     */
    if (empty($_POST['serviceNeedRestart'])) {
        $serviceNeedRestart = array();
    } else {
        $serviceNeedRestart = $_POST['serviceNeedRestart'];
    }

    /**
     *  Profile notes
     */
    if (empty($_POST['notes'])) {
        $notes = '';
    } else {
        $notes = $_POST['notes'];
    }

    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->configure($_POST['name'], $reposList, $packagesExcluded, $packagesMajorExcluded, $serviceNeedRestart, $_POST['linupdateGetPkgConf'], $_POST['linupdateGetReposConf'], $notes);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "<b>" . $_POST['name'] . "</b> profile configuration has been saved");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
