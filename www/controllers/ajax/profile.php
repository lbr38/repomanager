<?php

/*
 *  Create a new profile
 */
if ($_POST['action'] == 'new' and !empty($_POST['name'])) {
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
if ($_POST['action'] == 'delete' and !empty($_POST['id'])) {
    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->delete($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Profile has been deleted');
}

/**
 *  Duplicate a profile
 */
if ($_POST['action'] == 'duplicate' and !empty($_POST['id'])) {
    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->duplicate($_POST['id']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Profile has been duplicated');
}

/**
 *  Configure a profile
 */
if ($_POST['action'] == 'configure' and !empty($_POST['id']) and !empty($_POST['name'])) {
    $reposList = array();
    $exclude = array();
    $excludeMajor = array();
    $serviceReload = array();
    $serviceRestart = array();
    $notes = '';

    /**
     *  If no repos have been specified then it means that the user wants to set it clear, so set $reposList as en empty array
     */
    if (!empty($_POST['reposList'])) {
        $reposList = $_POST['reposList'];
    }

    /**
     *  If no package to exclude have been specified then it means that the user wants to set it clear, so set $exclude as en empty array
     */
    if (!empty($_POST['exclude'])) {
        $exclude = $_POST['exclude'];
    }

    /**
     *  If no package to exclude (on major version) have been specified then it means that the user wants to set it clear, so set $excludeMajor as en empty array
     */
    if (!empty($_POST['excludeMajor'])) {
        $excludeMajor = $_POST['excludeMajor'];
    }

    /**
     *  If no service to reload have been specified then it means that the user wants to set it clear, so set $serviceReload as en empty array
     */
    if (!empty($_POST['serviceReload'])) {
        $serviceReload = $_POST['serviceReload'];
    }

    /**
     *  If no service to restart have been specified then it means that the user wants to set it clear, so set $exclude as en empty array
     */
    if (!empty($_POST['serviceRestart'])) {
        $serviceRestart = $_POST['serviceRestart'];
    }

    /**
     *  Profile notes
     */
    if (!empty($_POST['notes'])) {
        $notes = $_POST['notes'];
    }

    $myprofile = new \Controllers\Profile();

    try {
        $myprofile->configure($_POST['id'], $_POST['name'], $reposList, $exclude, $excludeMajor, $serviceReload, $serviceRestart, $notes);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "<b>" . $_POST['name'] . "</b> profile configuration has been saved");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
