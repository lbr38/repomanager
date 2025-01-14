<?php
$id = __ACTUAL_URI__[2];
$hostPackageController = new \Controllers\Host\Package\Package($id);
$myhost = new \Controllers\Host();

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
$packagesInventored = $hostPackageController->getInventory();
$packagesInstalledCount = count($hostPackageController->getInstalled());

/**
 *  Getting available packages and its total
 */
$packagesAvailableTotal = count($hostPackageController->getAvailable());

unset($myhost, $hostPackageController);
