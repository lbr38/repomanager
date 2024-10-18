<?php
/**
 *  Add a new source repo
 */
if ($_POST['action'] == 'new' and !empty($_POST['params'])) {
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->new($_POST['params']);
    } catch (\Exception $e) {
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
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Source repository edited');
}

/**
 *  Delete a source repo
 */
if ($_POST['action'] == 'delete' and !empty($_POST['sourceId'])) {
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->delete($_POST['sourceId']);
    } catch (\Exception $e) {
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
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Source repositories have been imported');
}

/**
 *  Delete a GPG key
 */
if ($_POST['action'] == 'deleteGpgKey' and !empty($_POST['gpgKeyId'])) {
    $mysource = new \Controllers\Repo\Source\Source();

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
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->importGpgKey($_POST['gpgkey']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, "GPG key has been imported");
}

/**
 *  Edit a distribution
 */
if ($_POST['action'] == 'distribution/edit' and !empty($_POST['id']) and !empty($_POST['distribution']) and !empty($_POST['params'])) {
    $mysource = new \Controllers\Repo\Source\Deb();

    try {
        $mysource->editDistribution($_POST['id'], $_POST['distribution'], $_POST['params']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution edited');
}






response(HTTP_BAD_REQUEST, 'Invalid action');
