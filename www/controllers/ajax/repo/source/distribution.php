<?php
use \Controllers\Repo\Source\Deb as DebSourceRepo;

/**
 *  Add a new distribution
 */
if ($_POST['action'] == 'add' and !empty($_POST['id']) and !empty($_POST['name'])) {
    $rpmSourceController = new DebSourceRepo($_POST['id']);

    try {
        $rpmSourceController->addDistribution($_POST['id'], $_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution added');
}

/**
 *  Edit a distribution
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['params'])) {
    $rpmSourceController = new DebSourceRepo($_POST['id']);

    try {
        $rpmSourceController->editDistribution($_POST['distributionId'], $_POST['params']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution edited');
}

/**
 *  Remove a distribution
 */
if ($_POST['action'] == 'remove' and !empty($_POST['id']) and isset($_POST['distributionId'])) {
    $rpmSourceController = new DebSourceRepo($_POST['id']);

    try {
        $rpmSourceController->removeDistribution($_POST['distributionId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution removed');
}

/**
 *  Add a distribution GPG key
 */
if ($_POST['action'] == 'add-gpgkey' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['gpgKeyUrl']) and isset($_POST['gpgKeyFingerprint']) and isset($_POST['gpgKeyPlainText'])) {
    $rpmSourceController = new DebSourceRepo($_POST['id']);

    try {
        $rpmSourceController->addGpgKey($_POST['distributionId'], $_POST['gpgKeyUrl'], $_POST['gpgKeyFingerprint'], $_POST['gpgKeyPlainText']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key added');
}

/**
 *  Remove a distribution GPG key
 */
if ($_POST['action'] == 'remove-gpgkey' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['gpgkeyId'])) {
    $rpmSourceController = new DebSourceRepo($_POST['id']);

    try {
        $rpmSourceController->removeGpgKey($_POST['distributionId'], $_POST['gpgkeyId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key removed');
}

/**
 *  Get predefined distributions values for a task
 */
if ($_POST['action'] == 'get-predefined-distributions' and !empty($_POST['source'])) {
    $rpmSourceController = new DebSourceRepo();

    try {
        $content = $rpmSourceController->getPredefinedDistributions($_POST['source']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
