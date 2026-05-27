<?php
use \Controllers\User\Permission\Repo as RepoPermission;

$repoController = new \Controllers\Repo\Repo();
$gpgSignChecked = '';

// If the user does not have permission to rebuild repositories, prevent access to this panel.
if (!RepoPermission::allowedAction('rebuild')) {
    throw new Exception('You are not allowed to rebuild repositories');
}

if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('Snapshot ID is missing');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('Invalid snapshot ID');
}

$snapId = __ACTUAL_URI__[2];

// Retrieve repository infos from DB
$repoController->getAllById(null, $snapId);

// Retrieve snapshot rebuild status
$rebuild = $repoController->getRebuild();

if (!empty($rebuild) and $rebuild == 'running') {
    throw new Exception('Snapshot metadata rebuild is currently running. You cannot rebuild metadata at this time.');
}

// Define if the GPG signature checkbox should be checked by default
if ($repoController->getPackageType() == 'rpm' && RPM_SIGN_PACKAGES == 'true') {
    $gpgSignChecked = 'checked';
}
if ($repoController->getPackageType() == 'deb' && DEB_SIGN_REPO == 'true') {
    $gpgSignChecked = 'checked';
}
