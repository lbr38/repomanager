<?php
$myhost = new \Controllers\Host();

$id = __ACTUAL_URI__[2];

/**
 *  Open host database
 */
$myhost->openHostDb($id);

/**
 *  Getting hosts general threshold settings
 */
$hosts_settings = $myhost->getSettings();

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
 */
$pkgs_count_considered_outdated = $hosts_settings['pkgs_count_considered_outdated'];

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (critical)
 */
$pkgs_count_considered_critical = $hosts_settings['pkgs_count_considered_critical'];

/**
 *  Getting installed packages and its total
 */
$packagesInventored = $myhost->getPackagesInventory();
$packagesInstalledCount = count($myhost->getPackagesInstalled());

/**
 *  Getting available packages and its total
 */
$packagesAvailableTotal = count($myhost->getPackagesAvailable());

/**
 *  Close host database
 */
$myhost->closeHostDb();

unset($myhost);
