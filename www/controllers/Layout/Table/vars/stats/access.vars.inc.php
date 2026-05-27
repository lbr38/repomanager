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
if (!empty($_COOKIE['tables/stats/access/period'])) {
    $timeStart = strtotime(explode(' - ', $_COOKIE['tables/stats/access/period'])[0]);
    $timeEnd = strtotime(explode(' - ', $_COOKIE['tables/stats/access/period'])[1]);
}

// Retrieve envs from cookie if exists
if (!empty($_COOKIE['tables/stats/access/envs'])) {
    $envs = json_decode($_COOKIE['tables/stats/access/envs'], true);
}

// Retrieve offset from cookie if exists
if (!empty($_COOKIE['tables/stats/access/offset']) and is_numeric($_COOKIE['tables/stats/access/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/stats/access/offset'];
}

// Retrieve last access logs, with offset
if ($repoController->getPackageType() == 'rpm') {
    $reloadableTableContent = $rpmRepoStatController->getAccess($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd, false, true, $reloadableTableOffset);
}
if ($repoController->getPackageType() == 'deb') {
    $reloadableTableContent = $debRepoStatController->getAccess($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd, false, true, $reloadableTableOffset);
}

// Retrieve last access logs, without offset, for the total count
if ($repoController->getPackageType() == 'rpm') {
    $reloadableTableTotalItems = $rpmRepoStatController->getAccess($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd, true);
}
if ($repoController->getPackageType() == 'deb') {
    $reloadableTableTotalItems = $debRepoStatController->getAccess($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd, true);
}

// Count total pages for the pagination
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

// Calculate current page number
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($repoController, $debRepoStatController, $rpmRepoStatController, $envs);
