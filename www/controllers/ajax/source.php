<?php
/**
 *  Add a new source repo
 */
if ($_POST['action'] == 'new' and !empty($_POST['params'])) {
    $mysource = new \Controllers\Repo\Source\Source();

    try {
        $mysource->new('manual', $_POST['params']);
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
 *  Add a new distribution
 */
if ($_POST['action'] == 'distribution/add' and !empty($_POST['id']) and !empty($_POST['name'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->addDistribution($_POST['id'], $_POST['name']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution added');
}

/**
 *  Edit a distribution
 */
if ($_POST['action'] == 'distribution/edit' and !empty($_POST['id']) and isset($_POST['distributionId']) and isset($_POST['params'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->editDistribution($_POST['id'], $_POST['distributionId'], $_POST['params']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution edited');
}

/**
 *  Remove a distribution
 */
if ($_POST['action'] == 'distribution/remove' and !empty($_POST['id']) and isset($_POST['distributionId'])) {
    $myDebSource = new \Controllers\Repo\Source\Deb();

    try {
        $myDebSource->removeDistribution($_POST['id'], $_POST['distributionId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Distribution removed');
}

/**
 *  Add a new release version
 */
if ($_POST['action'] == 'releasever/add' and !empty($_POST['id']) and !empty($_POST['name'])) {
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
if ($_POST['action'] == 'releasever/edit' and !empty($_POST['id']) and isset($_POST['releaseverId']) and isset($_POST['params'])) {
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
if ($_POST['action'] == 'releasever/remove' and !empty($_POST['id']) and isset($_POST['releaseverId'])) {
    $myRpmSource = new \Controllers\Repo\Source\Rpm();

    try {
        $myRpmSource->removeReleasever($_POST['id'], $_POST['releaseverId']);
    } catch (\Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Release version removed');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
