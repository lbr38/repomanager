<?php
$repoController = new \Controllers\Repo\Repo();
$debRepoStatController = new \Controllers\Repo\Statistic\Deb();
$rpmRepoStatController = new \Controllers\Repo\Statistic\Rpm();
$reloadableTableOffset = 0;
$timeStart = strtotime(DATE_YMD . ' 00:00:00');
$timeEnd = strtotime(DATE_YMD . ' 23:59:59');
$envs = [];

// Check that repository Id is specified
if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no repository ID specified');
}

// Check that repository Id is valid
if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid repository ID');
}

// Get repository info
$repoController->getAllById(__ACTUAL_URI__[2]);

// Retrieve period from cookie if exists
if (!empty($_COOKIE['tables/stats/ip-access/period'])) {
    $timeStart = strtotime(explode(' - ', $_COOKIE['tables/stats/ip-access/period'])[0]);
    $timeEnd = strtotime(explode(' - ', $_COOKIE['tables/stats/ip-access/period'])[1]);
}

// Retrieve envs from cookie if exists
if (!empty($_COOKIE['tables/stats/ip-access/envs'])) {
    $envs = json_decode($_COOKIE['tables/stats/ip-access/envs'], true);
}

// Retrieve offset from cookie if exists
if (!empty($_COOKIE['tables/stats/ip-access/offset']) and is_numeric($_COOKIE['tables/stats/ip-access/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/stats/ip-access/offset'];
}

// Get access by IP count for the specified date, with offset
if ($repoController->getPackageType() == 'rpm') {
    $reloadableTableContent = $rpmRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd, false, true, $reloadableTableOffset);
}
if ($repoController->getPackageType() == 'deb') {
    $reloadableTableContent = $debRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd, false, true, $reloadableTableOffset);
}

// Get access by IP count for the specified date, without offset, for the total count
if ($repoController->getPackageType() == 'rpm') {
    $reloadableTableTotalItems = $rpmRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd, true);
}
if ($repoController->getPackageType() == 'deb') {
    $reloadableTableTotalItems = $debRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd, true);
}

// Count total pages for the pagination
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

// Calculate current page number
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($repoController, $debRepoStatController, $rpmRepoStatController, $timeStart, $timeEnd, $envs);
