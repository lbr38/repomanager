<?php
use \Controllers\Filesystem\Directory;
use \Controllers\Filesystem\File;
use \Controllers\Utils\Convert;
use \Controllers\Repo\Browse;
use \Controllers\User\Permission\Repo as RepoPermission;

// The user must be allowed to browse repositories
if (!RepoPermission::allowedAction('browse')) {
    throw new Exception('you are not allowed to browse repositories.');
}

// Mirror snapshot (/snapshot/mirror/<id>): read-only, its content is an exact copy of the source repository
$isMirror = (__ACTUAL_URI__[2] ?? '') == 'mirror';

if ($isMirror) {
    $snapId = __ACTUAL_URI__[3] ?? '';
    $snapshotPath = (new \Controllers\Mirror\Snapshot())->getBrowseInfo($snapId)['path'];

    // Permission to view the mirror has been checked when retrieving its info, allow the user to browse it
    Browse::allowRoot($snapshotPath);

    $protectedEnv = false;
    $taskRunning = false;
    return;
}

$repoController = new \Controllers\Repo\Repo();
$repoSnapshotController = new \Controllers\Repo\Snapshot\Snapshot();

if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no repository snapshot ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid repository snapshot ID.');
}

$snapId = __ACTUAL_URI__[2];

// Check if the snapshot exists
if (!$repoSnapshotController->exists($snapId)) {
    throw new Exception('repository snapshot #' . $snapId . ' does not exist.');
}

// Check if the snapshot has a protected environment
$protectedEnv = $repoSnapshotController->hasProtectedEnv($snapId);

// Check if a task is running for this snapshot
$taskRunning = $repoSnapshotController->taskRunning($snapId);

// Retrieve repo infos from DB
$repoController->getAllById('', $snapId, '');

// Check that the user is allowed to view the repository
if (!RepoPermission::allowedToView($repoController->getRepoId())) {
    throw new Exception('repository snapshot #' . $snapId . ' does not exist.');
}

// Define snapshot path
if ($repoController->getPackageType() == 'rpm') {
    $snapshotPath = REPOS_DIR . '/rpm/' . $repoController->getName() . '/' . $repoController->getReleasever() . '/' . $repoController->getDate();
}

if ($repoController->getPackageType() == 'deb') {
    $snapshotPath = REPOS_DIR . '/deb/' . $repoController->getName() . '/' . $repoController->getDist() . '/' . $repoController->getSection() . '/' . $repoController->getDate();
}

// If the path does not exist on the server then we quit
if (!is_dir($snapshotPath)) {
    throw new Exception('snapshot directory ' . $snapshotPath . ' does not exist.');
}

// Allow the user to browse the snapshot directory
Browse::allowRoot($snapshotPath);

// Retrieve repo size and packages count
if ($repoController->getPackageType() == 'rpm') {
    $repoSize = Directory::getSize($snapshotPath);
    $packagesCount = count(File::findRecursive($snapshotPath, [], ['rpm']));
}
if ($repoController->getPackageType() == 'deb') {
    $repoSize = Directory::getSize($snapshotPath);
    $packagesCount = count(File::findRecursive($snapshotPath, [], ['deb']));
}

// Convert repo size in the most suitable byte format
$repoSize = Convert::sizeToHuman($repoSize);

unset($repoController, $repoSnapshotController);
