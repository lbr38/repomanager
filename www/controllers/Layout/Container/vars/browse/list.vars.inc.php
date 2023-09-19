<?php
$myrepo = new \Controllers\Repo\Repo();
$myop = new \Controllers\Operation\Operation();

if (empty(__ACTUAL_URI__[2])) {
    die('Error: no repo snapshot ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    die('Error: invalid repo snapshot ID.');
}

$snapId = __ACTUAL_URI__[2];

/**
 *  Retrieve repo infos from DB
 */
// $myrepo->setSnapId($snapId);
$myrepo->getAllById('', $snapId, '');

/**
 *  Si on n'a eu aucune erreur lors de la récupération des paramètres, alors on peut construire le chemin complet du repo
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
    die('Error: repo directory ' . $repoPath . ' does not exist.');
}

/**
 *  Retrieve repo size and packages count
 */
if ($myrepo->getPackageType() == 'rpm') {
    $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName());
    $packagesCount = count(\Controllers\Common::findRecursive(REPOS_DIR . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getName(), 'rpm'));
}
if ($myrepo->getPackageType() == 'deb') {
    $repoSize = \Controllers\Filesystem\Directory::getSize(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection());
    $packagesCount = count(\Controllers\Common::findRecursive(REPOS_DIR . '/' . $myrepo->getName() . '/' . $myrepo->getDist() . '/' . $myrepo->getDateFormatted() . '_' . $myrepo->getSection(), 'deb'));
}

/**
 *  Convert repo size in the most suitable byte format
 */
$repoSize = \Controllers\Common::sizeFormat($repoSize);
