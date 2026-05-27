<?php
use \Controllers\Filesystem\Directory;
use \Controllers\Filesystem\File;
use \Controllers\Utils\Convert;

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
