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
 *  browse/list container should have already throwed an error
 */
if (!is_dir($repoPath)) {
    throw new Exception();
}

/**
 *  Retrieve repo rebuild status
 */
$rebuild = $myrepo->getRebuild();

/**
 *  Upload packages
 */
if (!empty($_POST['action']) and $_POST['action'] == 'uploadPackage' and !empty($_POST['snapId']) and is_numeric($_POST['snapId']) and !empty($_FILES['packages'])) {
    $myrepoPackage = new \Controllers\Repo\Package();

    try {
        $myrepoPackage->upload($_POST['snapId'], \Controllers\Browse::reArrayFiles($_FILES['packages']));
        $uploadSuccessMessage = 'Packages uploaded successfully';
    } catch (\Exception $e) {
        $uploadErrorMessage = $e->getMessage();
    }

    unset($myrepoPackage);
}

unset($repoSnapshotController);
