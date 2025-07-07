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
 *  Build repo path
 */
if ($myrepo->getPackageType() == 'rpm') {
    $repoPath = REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName();
}

if ($myrepo->getPackageType() == 'deb') {
    $repoPath = REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection();
}

/**
 *  If the path does not exist on the server then we quit
 */
if (!is_dir($repoPath)) {
    throw new Exception('Error: repo directory ' . $repoPath . ' does not exist.');
}

/**
 *  Retrieve repo size and packages count
 */
if ($myrepo->getPackageType() == 'rpm') {
    $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName());
    $packagesCount = count(\Controllers\Filesystem\File::findRecursive(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName(), ['rpm']));
}
if ($myrepo->getPackageType() == 'deb') {
    $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection());
    $packagesCount = count(\Controllers\Filesystem\File::findRecursive(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection(), ['deb']));
}

/**
 *  Convert repo size in the most suitable byte format
 */
$repoSize = \Controllers\Common::sizeFormat($repoSize);

unset($repoSnapshotController);
