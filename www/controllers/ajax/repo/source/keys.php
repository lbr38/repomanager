<?php
use Controllers\User\Permission\Repo as RepoPermission;

/**
 *  Only users allowed to edit source repositories can modify them
 */
if (!RepoPermission::allowedAction('edit-source')) {
    response(HTTP_BAD_REQUEST, 'You are not allowed to perform this action');
}

use \Controllers\Repo\Source\Items as ItemsSourceRepo;
use \Controllers\Repo\Source\Arch as ArchSourceRepo;
use \Controllers\Repo\Source\Alpine as AlpineSourceRepo;
use \Controllers\Repo\Source\Openbsd as OpenbsdSourceRepo;

/**
 *  Keys of the arch, alpine and openbsd source repositories (items are handled by the repo/source/arch controller):
 *   - GPG keys of the arch source repositories: imported in the trusted keyring, their fingerprints are stored in the definition
 *   - public keys of the alpine (RSA) and openbsd (signify) source repositories, stored in the definition
 */

/**
 *  Return the source repository controller, if its public keys are stored in its definition
 */
$getController = function (string $id): AlpineSourceRepo|OpenbsdSourceRepo {
    $sourceController = ItemsSourceRepo::fromId((int) $id);

    if (!$sourceController instanceof AlpineSourceRepo and !$sourceController instanceof OpenbsdSourceRepo) {
        throw new Exception('This source repository has no public keys to edit');
    }

    return $sourceController;
};

/**
 *  Return the arch source repository controller
 */
$getArchController = function (string $id): ArchSourceRepo {
    $sourceController = ItemsSourceRepo::fromId((int) $id);

    if (!$sourceController instanceof ArchSourceRepo) {
        throw new Exception('This source repository has no GPG keys to edit');
    }

    return $sourceController;
};

/**
 *  Add a public key from its URL or its content (plain text)
 */
if ($_POST['action'] == 'add-key' and !empty($_POST['id']) and isset($_POST['url']) and isset($_POST['content']) and isset($_POST['name'])) {
    try {
        $getController($_POST['id'])->addKey($_POST['url'], $_POST['content'], $_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Key added');
}

/**
 *  Remove a public key
 */
if ($_POST['action'] == 'remove-key' and !empty($_POST['id']) and isset($_POST['name'])) {
    try {
        $getController($_POST['id'])->removeKey($_POST['name']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'Key removed');
}

/**
 *  Add a GPG key to an arch source repository, from its URL, its fingerprint or plain text
 */
if ($_POST['action'] == 'add-gpgkey' and !empty($_POST['id']) and isset($_POST['gpgKeyUrl']) and isset($_POST['gpgKeyFingerprint']) and isset($_POST['gpgKeyPlainText'])) {
    try {
        $getArchController($_POST['id'])->addGpgKey($_POST['gpgKeyUrl'], $_POST['gpgKeyFingerprint'], $_POST['gpgKeyPlainText']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key added');
}

/**
 *  Remove a GPG key from an arch source repository
 */
if ($_POST['action'] == 'remove-gpgkey' and !empty($_POST['id']) and isset($_POST['gpgkeyId']) and is_numeric($_POST['gpgkeyId'])) {
    try {
        $getArchController($_POST['id'])->removeGpgKey((int) $_POST['gpgkeyId']);
    } catch (Exception $e) {
        response(HTTP_BAD_REQUEST, $e->getMessage());
    }

    response(HTTP_OK, 'GPG key removed');
}

response(HTTP_BAD_REQUEST, 'Invalid action');
