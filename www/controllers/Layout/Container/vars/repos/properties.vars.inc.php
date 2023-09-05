<?php
$myrepo = new \Controllers\Repo\Repo();
$myplan = new \Controllers\Planification();

/**
 *  Get total repos count
 */
$totalRepos = $myrepo->count('active');

/**
 *  Get used space
 */
$diskTotalSpace = disk_total_space(REPOS_DIR);
$diskFreeSpace = disk_free_space(REPOS_DIR);
$diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
$diskTotalSpace = $diskTotalSpace / 1073741824;
$diskUsedSpace = $diskUsedSpace / 1073741824;

/**
 *  Format data to get a percent result without comma
 */
$diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
$diskFreeSpacePercent = $diskFreeSpace;
$diskUsedSpace = round(100 - ($diskFreeSpace));
$diskUsedSpacePercent = round(100 - ($diskFreeSpace));

/**
 *  If plans are enabled the get last and next plan results
 */
if (PLANS_ENABLED == "true") {
    $lastPlan = $myplan->listLast();
    $nextPlan = $myplan->listNext();
}

/**
 *  Get current CPU load
 */
$currentLoad = sys_getloadavg();
$currentLoad = substr($currentLoad[0], 0, 4);
$currentLoadColor = 'green';

if ($currentLoad >= 2) {
    $currentLoadColor = 'yellow';
}
if ($currentLoad >= 3) {
    $currentLoadColor = 'red';
}

unset($myrepo, $myplan);
