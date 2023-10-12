<?php
$myrepo = new \Controllers\Repo\Repo();

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
 *  Retrieve repo reconstruct status
 */
$reconstruct = $myrepo->getReconstruct();

/**
 *  Upload packages
 */
if (!empty($_POST['action']) and $_POST['action'] == 'uploadPackage' and !empty($_POST['snapId']) and is_numeric($_POST['snapId']) and !empty($_FILES['packages'])) {
    $myrepoPackage = new \Controllers\Repo\Package();

    try {
        $myrepoPackage->upload($_POST['snapId'], $_FILES['packages']);
        $uploadSuccessMessage = '<br>Packages uploaded successfully';
    } catch (\Exception $e) {
        $uploadErrorMessage = $e->getMessage();
    }

    unset($myrepoPackage);
}
