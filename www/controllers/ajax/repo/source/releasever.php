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
 *  Add a release version GPG key
 */
if ($_POST['action'] == 'add-gpgkey' and !empty($_POST['id']) and isset($_POST['releaseverId']) and isset($_POST['gpgKeyUrl']) and isset($_POST['gpgKeyFingerprint']) and isset($_POST['gpgKeyPlainText'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $myRpmSource->addGpgKey($_POST['id'], $_POST['releaseverId'], $_POST['gpgKeyUrl'], $_POST['gpgKeyFingerprint'], $_POST['gpgKeyPlainText']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key added');
}

/**
 *  Remove a release version GPG key
 */
if ($_POST['action'] == 'remove-gpgkey' and !empty($_POST['id']) and isset($_POST['releaseverId']) and isset($_POST['gpgkeyId'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $myRpmSource->removeGpgKey($_POST['id'], $_POST['releaseverId'], $_POST['gpgkeyId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key removed');
}

/**
 *  Get predefined release versions values for a task
 */
if ($_POST['action'] == 'get-predefined-releasevers' and !empty($_POST['source'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $content = $myRpmSource->getPredefinedReleasever($_POST['source']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
