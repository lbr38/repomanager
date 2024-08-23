<?php
$mygroup = new \Controllers\Group('host');
$myhost = new \Controllers\Host();
$hostDb = new \Controllers\Host();

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
