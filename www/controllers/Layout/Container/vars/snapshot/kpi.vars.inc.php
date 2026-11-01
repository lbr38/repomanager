<?php
use \Controllers\Filesystem\Directory;
use \Controllers\Filesystem\File;
use \Controllers\Utils\Convert;

// Mirror snapshot (/snapshot/mirror/<id>): read-only, its content is an exact copy of the source repository
$isMirror = (__ACTUAL_URI__[2] ?? '') == 'mirror';

if ($isMirror) {
    $mirrorBrowse = (new \Controllers\Mirror\Snapshot())->getBrowseInfo(__ACTUAL_URI__[3] ?? '');
    $repository = $mirrorBrowse['label'];
    $accent = $mirrorBrowse['accent'];
    $dateFormatted = $mirrorBrowse['date'];
    $repoSize = Convert::sizeToHuman($mirrorBrowse['size']);
    $packagesCount = $mirrorBrowse['packagesCount'];
    $rebuild = null;
    unset($mirrorBrowse);
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

// Retrieve repository infos from DB
$repoController->getAllById(null, $snapId, null);

// Define repository name
if ($repoController->getPackageType() == 'rpm') {
    $repository = $repoController->getName() . ' ❯ ' . $repoController->getReleasever();
}
if ($repoController->getPackageType() == 'deb') {
    $repository = $repoController->getName() . ' ❯ ' . $repoController->getDist() . ' ❯ ' . $repoController->getSection();
}

// Define snapshot path
if ($repoController->getPackageType() == 'rpm') {
    $snapshotPath = REPOS_DIR . '/rpm/' . $repoController->getName() . '/' . $repoController->getReleasever() . '/' . $repoController->getDate();
}
if ($repoController->getPackageType() == 'deb') {
    $snapshotPath = REPOS_DIR . '/deb/' . $repoController->getName() . '/' . $repoController->getDist() . '/' . $repoController->getSection() . '/' . $repoController->getDate();
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

// Retrieve snapshot rebuild status
$rebuild = $repoController->getRebuild();

$dateFormatted = $repoController->getDateFormatted();

unset($repoSnapshotController);
