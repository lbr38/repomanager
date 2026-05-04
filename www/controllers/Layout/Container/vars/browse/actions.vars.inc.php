<?php
use \Controllers\Exception\AppException;
use \Controllers\Utils\Array\Sort;
use \Controllers\Utils\Convert;

$repoController = new \Controllers\Repo\Repo();

if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('Error: no repository snapshot ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('Error: invalid repository snapshot ID.');
}

$snapId = __ACTUAL_URI__[2];

// Instanciate repo snapshot package controller (it will also check if the snapshot exists)
$repoPackageController = new \Controllers\Repo\Snapshot\Package($snapId);

/**
 *  Retrieve repo infos from DB
 */
$repoController->getAllById('', $snapId, '');

/**
 *  Define snapshot path
 */
if ($repoController->getPackageType() == 'rpm') {
    $snapshotPath = REPOS_DIR . '/rpm/' . $repoController->getName() . '/' . $repoController->getReleasever() . '/' . $repoController->getDate();
}

if ($repoController->getPackageType() == 'deb') {
    $snapshotPath = REPOS_DIR . '/deb/' . $repoController->getName() . '/' . $repoController->getDist() . '/' . $repoController->getSection() . '/' . $repoController->getDate();
}

/**
 *  If the path does not exist on the server then we quit
 *  browse/list container should have already throwed an error
 */
if (!is_dir($snapshotPath)) {
    throw new Exception();
}

/**
 *  Retrieve repo rebuild status
 */
$rebuild = $repoController->getRebuild();

/**
 *  Upload packages
 */
if (!empty($_POST['action']) and $_POST['action'] == 'uploadPackage' and !empty($_POST['snapId']) and is_numeric($_POST['snapId']) and !empty($_FILES['packages'])) {
    try {
        if (isset($_POST['overwrite']) && is_null($overwrite = Convert::toBool($_POST['overwrite']))) {
            throw new Exception('Invalid overwrite value');
        }

        $repoPackageController->upload(Sort::byPostFiles($_FILES['packages']), $overwrite ?? false);
        $uploadSuccessMessage = 'Packages uploaded successfully';
    } catch (AppException $e) {
        $uploadErrorDetails = $e->getDetails();
    } catch (Exception $e) {
        $uploadErrorMessage = $e->getMessage();
    }

    unset($repoPackageController);
}
