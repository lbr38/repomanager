<?php
$myrepo = new \Controllers\Repo\Repo();
$mytask = new \Controllers\Task\Task();

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
 *  If scheduled tasks are enabled the get last and next plan results
 */
// $lastPlan = $mytask->listLast();
// $nextPlan = $mytask->listNext();

unset($myrepo, $mytask);
