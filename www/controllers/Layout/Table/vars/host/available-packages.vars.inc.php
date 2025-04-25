<?php
$id = __ACTUAL_URI__[2];
$hostController = new \Controllers\Host();
$hostPackageController = new \Controllers\Host\Package\Package($id);
$hostRequestController = new \Controllers\Host\Request();
$profileController = new \Controllers\Profile();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/host/available-packages/offset']) and is_numeric($_COOKIE['tables/host/available-packages/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/host/available-packages/offset'];
}

/**
 *  Get host informations
 */
$host = $hostController->getAll($id);

/**
 *  Get list of available packages updates, with offset
 */
$reloadableTableContent = $hostPackageController->getAvailable(true, $reloadableTableOffset);

/**
 *  Get list of ALL available packages updates, without offset, for the total count
 */
$reloadableTableTotalItems = count($hostPackageController->getAvailable());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

/**
 *  Check if there is a package update running
 */
$packageUpdateRunning = $hostRequestController->isPackageUpdateRequestRunning($id);

/**
 *  Get the host profile configuration to retrieve the list of packages that are excluded
 */
$profileConfiguration = $profileController->getProfilePackagesConfiguration($host['Profile']);

/**
 *  Get the list of packages that are excluded from updates
 */
$packageExcluded = array_filter(explode(',', $profileConfiguration['Package_exclude']));

/**
 *  Get the list of packages that are excluded from major updates
 */
$packageExcludedMajor = array_filter(explode(',', $profileConfiguration['Package_exclude_major']));

unset($hostController, $hostPackageController, $hostRequestController, $profileController, $host, $profileConfiguration);
