<?php
/**
 *  Add a new distribution
 */
if ($_POST['action'] == 'add' and !empty($_POST['id']) and !empty($_POST['name'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->addDistribution($_POST['id'], $_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution added');
}

/**
 *  Edit a distribution
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['params'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->editDistribution($_POST['id'], $_POST['distributionId'], $_POST['params']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution edited');
}

/**
 *  Remove a distribution
 */
if ($_POST['action'] == 'remove' and !empty($_POST['id']) and isset($_POST['distributionId'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->removeDistribution($_POST['id'], $_POST['distributionId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution removed');
}

/**
 *  Add a distribution section
 */
if ($_POST['action'] == 'add-section' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['section'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->addSection($_POST['id'], $_POST['distributionId'], $_POST['section']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Section added');
}

/**
 *  Remove a distribution section
 */
if ($_POST['action'] == 'remove-section' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['sectionId'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->removeSection($_POST['id'], $_POST['distributionId'], $_POST['sectionId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Component removed');
}

/**
 *  Add a distribution GPG key
 */
if ($_POST['action'] == 'add-gpgkey' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['gpgKeyUrl']) and isset($_POST['gpgKeyFingerprint']) and isset($_POST['gpgKeyPlainText'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->addGpgKey($_POST['id'], $_POST['distributionId'], $_POST['gpgKeyUrl'], $_POST['gpgKeyFingerprint'], $_POST['gpgKeyPlainText']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key added');
}

/**
 *  Remove a distribution GPG key
 */
if ($_POST['action'] == 'remove-gpgkey' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['gpgkeyId'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->removeGpgKey($_POST['id'], $_POST['distributionId'], $_POST['gpgkeyId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key removed');
}

/**
 *  Get predefined distributions values for a task
 */
if ($_POST['action'] == 'get-predefined-distributions' and !empty($_POST['source'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $content = $myDebSource->getPredefinedDistributions($_POST['source']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

/**
 *  Get predefined components values for a task
 */
if ($_POST['action'] == 'get-predefined-components' and !empty($_POST['source']) and !empty($_POST['distribution'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $content = $myDebSource->getPredefinedComponents($_POST['source'], $_POST['distribution']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, $content);
}

response(HTTP_BAD_REQUEST, 'Invalid action');
