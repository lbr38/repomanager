<?php
use \Controllers\Filesystem\Directory;
use \Controllers\Filesystem\File;
use \Controllers\Utils\Convert;

$repoController = new \Controllers\Repo\Repo();

if (empty(__ACTUAL_URI__[2])) {
    die('Error: no snapshot env ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    die('Error: invalid snapshot env ID.');
}

// Retrieve repo info
$repoController->getAllById('', '', __ACTUAL_URI__[2]);

// Count repo size and packages count
if ($repoController->getPackageType() == 'rpm') {
    $snapshotPath = REPOS_DIR . '/rpm/' . $repoController->getName() . '/' . $repoController->getReleasever() . '/' . $repoController->getDate();
    $repoSize = Directory::getSize($snapshotPath);
    $packagesCount = count(File::findRecursive($snapshotPath, ['rpm']));
}
if ($repoController->getPackageType() == 'deb') {
    $snapshotPath = REPOS_DIR . '/deb/' . $repoController->getName() . '/' . $repoController->getDist() . '/' . $repoController->getSection() . '/' . $repoController->getDate();
    $repoSize = Directory::getSize($snapshotPath);
    $packagesCount = count(File::findRecursive($snapshotPath, ['deb']));
}

// Convert repo size in the most suitable byte format
$repoSize = Convert::sizeToHuman($repoSize);

unset($snapshotPath);
