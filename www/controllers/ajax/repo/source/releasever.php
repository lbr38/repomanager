<?php
use \Controllers\Repo\Source\Rpm as RpmSourceRepo;

/**
 *  Add a new release version
 */
if ($_POST['action'] == 'add' and !empty($_POST['id']) and !empty($_POST['name'])) {
    $rpmSourceController = new RpmSourceRepo($_POST['id']);

    try {
        $rpmSourceController->addReleasever($_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Release version added');
}

/**
 *  Edit a release version
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and isset($_POST['releaseverId']) and isset($_POST['params'])) {
    $rpmSourceController = new RpmSourceRepo($_POST['id']);

    try {
        $rpmSourceController->editReleasever($_POST['releaseverId'], $_POST['params']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Release version edited');
}

/**
 *  Remove a release version
 */
if ($_POST['action'] == 'remove' and !empty($_POST['id']) and isset($_POST['releaseverId'])) {
    $rpmSourceController = new RpmSourceRepo($_POST['id']);

    try {
        $rpmSourceController->removeReleasever($_POST['releaseverId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Release version removed');
}

/**
 *  Add a release version GPG key
 */
if ($_POST['action'] == 'add-gpgkey' and !empty($_POST['id']) and isset($_POST['releaseverId']) and isset($_POST['gpgKeyUrl']) and isset($_POST['gpgKeyFingerprint']) and isset($_POST['gpgKeyPlainText'])) {
    $rpmSourceController = new RpmSourceRepo($_POST['id']);

    try {
        $rpmSourceController->addGpgKey($_POST['releaseverId'], $_POST['gpgKeyUrl'], $_POST['gpgKeyFingerprint'], $_POST['gpgKeyPlainText']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key added');
}

/**
 *  Remove a release version GPG key
 */
if ($_POST['action'] == 'remove-gpgkey' and !empty($_POST['id']) and isset($_POST['releaseverId']) and isset($_POST['gpgkeyId'])) {
    $rpmSourceController = new RpmSourceRepo($_POST['id']);

    try {
        $rpmSourceController->removeGpgKey($_POST['releaseverId'], $_POST['gpgkeyId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key removed');
}

/**
 *  Get predefined release versions values for a task
 */
if ($_POST['action'] == 'get-predefined-releasevers' and !empty($_POST['source'])) {
    $rpmSourceController = new RpmSourceRepo();

    try {
        $content = $rpmSourceController->getPredefinedReleasever($_POST['source']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get predefined release version architectures values for a task
 */
if ($_POST['action'] == 'get-predefined-architectures' and !empty($_POST['source']) and !empty($_POST['releasever'])) {
    $rpmSourceController = new RpmSourceRepo();

    try {
        $content = $rpmSourceController->getPredefinedArchitectures($_POST['source'], $_POST['releasever']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
