<?php
/**
 *  Add a new release version
 */
if ($_POST['action'] == 'add' and !empty($_POST['id']) and !empty($_POST['name'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $myRpmSource->addReleasever($_POST['id'], $_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Release version added');
}

/**
 *  Edit a release version
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and isset($_POST['releaseverId']) and isset($_POST['params'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $myRpmSource->editReleasever($_POST['id'], $_POST['releaseverId'], $_POST['params']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Release version edited');
}

/**
 *  Remove a release version
 */
if ($_POST['action'] == 'remove' and !empty($_POST['id']) and isset($_POST['releaseverId'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $myRpmSource->removeReleasever($_POST['id'], $_POST['releaseverId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Release version removed');
}

/**
 *  Remove a release version GPG key
 */
if ($_POST['action'] == 'remove-gpgkey' and !empty($_POST['id']) and isset($_POST['releaseverId']) and !empty($_POST['gpgkey'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $myRpmSource->removeGpgKey($_POST['id'], $_POST['releaseverId'], $_POST['gpgkey']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key removed');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
