<?php
$repoController = new \Controllers\Repo\Repo();
$debRepoStatController = new \Controllers\Repo\Statistic\Deb();
$rpmRepoStatController = new \Controllers\Repo\Statistic\Rpm();
$reloadableTableOffset = 0;
$timeStart = strtotime(DATE_YMD . ' 00:00:00');
$timeEnd = strtotime(DATE_YMD . ' 23:59:59');
$envs = [];

// Mirror page (/repository/mirror/<id>)
$isMirror = (__ACTUAL_URI__[2] ?? '') == 'mirror';

// Check that repository Id is specified
if (!$isMirror && empty(__ACTUAL_URI__[2])) {
    throw new Exception('no repository ID specified');
}

// Check that repository Id is valid
if (!$isMirror && !is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid repository ID');
}

// Get repository or mirror info
if ($isMirror) {
    $mirror = (new \Controllers\Mirror\Mirror())->getForPage(__ACTUAL_URI__[3] ?? '');
} else {
    $repoController->getAllById(__ACTUAL_URI__[2]);
}

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
if (!$isMirror && $repoController->getPackageType() == 'rpm') {
    $reloadableTableContent = $rpmRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd, false, true, $reloadableTableOffset);
}
if (!$isMirror && $repoController->getPackageType() == 'deb') {
    $reloadableTableContent = $debRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd, false, true, $reloadableTableOffset);
}

// Get access by IP count for the specified date, without offset, for the total count
if (!$isMirror && $repoController->getPackageType() == 'rpm') {
    $reloadableTableTotalItems = $rpmRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd, true);
}
if (!$isMirror && $repoController->getPackageType() == 'deb') {
    $reloadableTableTotalItems = $debRepoStatController->getAccessByIpCount($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd, true);
}

// Mirror accesses, with offset, then without offset for the total count
if ($isMirror) {
    $mirrorStatController = new \Controllers\Mirror\Statistic();
    $reloadableTableContent = $mirrorStatController->getAccessByIpCount($mirror['Id'], $envs, $timeStart, $timeEnd, false, true, $reloadableTableOffset);
    $reloadableTableTotalItems = $mirrorStatController->getAccessByIpCount($mirror['Id'], $envs, $timeStart, $timeEnd, true);
    unset($mirrorStatController, $mirror);
}

// Count total pages for the pagination
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

// Calculate current page number
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($repoController, $debRepoStatController, $rpmRepoStatController, $timeStart, $timeEnd, $envs);
