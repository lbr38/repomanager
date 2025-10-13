<?php
/**
 *  Add a new source repo
 */
if ($_POST['action'] == 'new' and !empty($_POST['params'])) {
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->new('manual', $_POST['params']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Source repository added');
}

/**
 *  Edit a source repo
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and !empty($_POST['params'])) {
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->edit($_POST['id'], $_POST['params']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Source repository edited');
}

/**
 *  Delete a source repo
 */
if ($_POST['action'] == 'delete' and !empty($_POST['id'])) {
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->delete($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Source repository deleted");
}

/**
 *  Import source repositories from list
 */
if ($_POST['action'] == 'import-source-repos' and !empty($_POST['list'])) {
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->import($_POST['list']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Source repositories have been imported');
}

/**
 *  Delete a GPG key
 */
if ($_POST['action'] == 'delete-gpgkey' and !empty($_POST['id'])) {
    $myGpg = new \Controllers\Gpg();

    try {
        $myGpg->delete($_POST['id']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "GPG key <b>" . $_POST['gpgKeyId'] . "</b> has been deleted");
}

/**
 *  Import a new GPG key
 */
if ($_POST['action'] == 'import-gpgkey' and isset($_POST['gpgKeyUrl']) and isset($_POST['gpgKeyFingerprint']) and isset($_POST['gpgKeyPlainText'])) {
    $myGpg = new \Controllers\Gpg();

    try {
        $myGpg->import($_POST['gpgKeyUrl'], $_POST['gpgKeyFingerprint'], $_POST['gpgKeyPlainText']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "GPG key has been imported");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
