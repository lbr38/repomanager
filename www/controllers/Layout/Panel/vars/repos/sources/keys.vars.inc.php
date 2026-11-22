<?php
use Controllers\User\Permission\Repo as RepoPermission;
use Controllers\Repo\Source\SigningKeys;

// If the user does not have permission to edit source repositories, prevent access to this panel.
if (!RepoPermission::allowedAction('edit-source')) {
    throw new Exception('You are not allowed to access this panel');
}

// GPG keys of the trusted keyring, with the source repositories referencing them
$gpgKeys = SigningKeys::getGpgKeys();

// RSA keys of the alpine source repositories and signify keys of the openbsd ones, by source repository
$sourcesKeys = [
    'alpine' => SigningKeys::getSourcesKeys('alpine'),
    'openbsd' => SigningKeys::getSourcesKeys('openbsd')
];
