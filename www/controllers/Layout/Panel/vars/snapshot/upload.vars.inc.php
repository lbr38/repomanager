<?php
use \Controllers\User\Permission\Repo as RepoPermission;

$repoController = new \Controllers\Repo\Repo();

// If the user does not have permission to upload packages, prevent access to this panel.
if (!RepoPermission::allowedAction('upload-package')) {
    throw new Exception('You are not allowed to upload packages');
}

if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('Snapshot ID is missing');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('Invalid snapshot ID');
}

$snapId = __ACTUAL_URI__[2];

// Instanciate repo snapshot package controller (it will also check if the snapshot exists)
$repoPackageController = new \Controllers\Repo\Snapshot\Package($snapId);

// Retrieve repository infos from DB
$repoController->getAllById(null, $snapId);

// Retrieve snapshot rebuild status
$rebuild = $repoController->getRebuild();

// Define snapshot path
if ($repoController->getPackageType() == 'rpm') {
    $snapshotPath = REPOS_DIR . '/rpm/' . $repoController->getName() . '/' . $repoController->getReleasever() . '/' . $repoController->getDate();
}

if ($repoController->getPackageType() == 'deb') {
    $snapshotPath = REPOS_DIR . '/deb/' . $repoController->getName() . '/' . $repoController->getDist() . '/' . $repoController->getSection() . '/' . $repoController->getDate();
}

// Check if the snapshot path exists
if (!is_dir($snapshotPath)) {
    throw new Exception('Snapshot path does not exist on the server');
}

// If a task is already running on this repo then print a message
if (!empty($rebuild) and $rebuild == 'running') {
    throw new Exception('A task is running on this repository snapshot. You cannot upload packages at this time.');
}
