<?php
$mygroup = new \Controllers\Group('host');
$myhost = new \Controllers\Host();
$hostRequestController = new \Controllers\Host\Request();
$compactView = true;

/**
 *  Initializing counters for doughnut chart
 */
$totalUptodate = 0;
$totalNotUptodate = 0;

/**
 *  Getting total hosts
 */
$totalHosts = count($myhost->listAll('active'));

/**
 *  Get hosts groups list
 */
$hostGroupsList = $mygroup->listAll(true);

/**
 *  Getting general hosts threshold settings
 */
$hostsSettings = $myhost->getSettings();

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
 */
$packagesCountConsideredOutdated = $hostsSettings['pkgs_count_considered_outdated'];

/**
 *  Threshold of the maximum number of available update above which the host is considered as 'not up to date' (critical)
 */
$packagesCountConsideredCritical = $hostsSettings['pkgs_count_considered_critical'];

if (isset($_COOKIE['hosts/compact-view']) and $_COOKIE['hosts/compact-view'] == false) {
    $compactView = false;
}

/**
 *  Setting layout variables depending on the view mode
 */
if ($compactView) {
    $layoutPackagesTitle = 'PKG.';
    $layoutGridClass = 'grid-5';
} else {
    $layoutPackagesTitle = 'PACKAGES';
    $layoutGridClass = 'grid-4';
}
