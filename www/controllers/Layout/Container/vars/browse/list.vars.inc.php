<?php
$myrepo = new \Controllers\Repo\Repo();
$repoSnapshotController = new \Controllers\Repo\Snapshot();

if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('Error: no repository snapshot ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('Error: invalid repository snapshot ID.');
}

$snapId = __ACTUAL_URI__[2];

/**
 *  Check if the snapshot exists
 */
if (!$repoSnapshotController->exists($snapId)) {
    throw new Exception('Error: repository snapshot #' . $snapId . ' does not exist.');
}

/**
 *  Retrieve repo infos from DB
 */
$myrepo->getAllById('', $snapId, '');

/**
 *  Define snapshot path
 */
if ($myrepo->getPackageType() == 'rpm') {
    $snapshotPath = REPOS_DIR . '/rpm/' . $myrepo->getName() . '/' . $myrepo->getReleasever() . '/' . $myrepo->getDate();
}

if ($myrepo->getPackageType() == 'deb') {
    $snapshotPath = REPOS_DIR . '/deb/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getSection() . '/' . $myrepo->getDate();
}

/**
 *  If the path does not exist on the server then we quit
 */
if (!is_dir($snapshotPath)) {
    throw new Exception('Error: repo directory ' . $snapshotPath . ' does not exist.');
}

/**
 *  Retrieve repo size and packages count
 */
if ($myrepo->getPackageType() == 'rpm') {
    $repoSize = \Controllers\Filesystem\Directory::getSize($snapshotPath);
    $packagesCount = count(\Controllers\Filesystem\File::findRecursive($snapshotPath, ['rpm']));
}
if ($myrepo->getPackageType() == 'deb') {
    $repoSize = \Controllers\Filesystem\Directory::getSize($snapshotPath);
    $packagesCount = count(\Controllers\Filesystem\File::findRecursive($snapshotPath, ['deb']));
}

/**
 *  Convert repo size in the most suitable byte format
 */
$repoSize = \Controllers\Utils\Convert::sizeToHuman($repoSize);

unset($repoSnapshotController);
