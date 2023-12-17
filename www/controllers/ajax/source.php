<?php

/**
 *  Add a new source repo
 */
if (
    $_POST['action'] == 'new'
    and !empty($_POST['repoType'])
    and !empty($_POST['name'])
    and !empty($_POST['url'])
    and isset($_POST['gpgKeyURL'])
    and isset($_POST['gpgKeyText'])
) {
    $mysource = new \Controllers\Source();

    try {
        $mysource->new($_POST['repoType'], $_POST['name'], $_POST['url'], $_POST['gpgKeyURL'], $_POST['gpgKeyText']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Source repo <b>' . $_POST['name'] . '</b> has been added');
}

/**
 *  Edit a source repo
 */
if ($_POST['action'] == 'edit' and !empty($_POST['id']) and !empty($_POST['name']) and !empty($_POST['url']) and isset($_POST['gpgkey']) and isset($_POST['sslCertificatePath']) and isset($_POST['sslPrivateKeyPath'])) {
    $mysource = new \Controllers\Source();

    try {
        $mysource->edit($_POST['id'], $_POST['name'], $_POST['url'], $_POST['gpgkey'], $_POST['sslCertificatePath'], $_POST['sslPrivateKeyPath']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Source repo has been edited');
}

/**
 *  Delete a source repo
 */
if ($_POST['action'] == 'delete' and !empty($_POST['sourceId'])) {
    $mysource = new \Controllers\Source();

    try {
        $mysource->delete($_POST['sourceId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "Source repo has been deleted");
}

/**
 *  Delete a GPG key
 */
if ($_POST['action'] == 'deleteGpgKey' and !empty($_POST['gpgKeyId'])) {
    $mysource = new \Controllers\Source();

    try {
        $mysource->deleteGpgKey($_POST['gpgKeyId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "GPG key <b>" . $_POST['gpgKeyId'] . "</b> has been deleted");
}

/**
 *  Import a new GPG key
 */
if ($_POST['action'] == 'importGpgKey' and !empty($_POST['gpgkey'])) {
    $mysource = new \Controllers\Source();

    try {
        $mysource->importGpgKey($_POST['gpgkey']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "GPG key has been imported");
}

response(HTTP_BAD_REQUEST, 'Invalid action');
