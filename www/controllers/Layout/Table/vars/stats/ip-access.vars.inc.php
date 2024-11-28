<?php
$mystats = new \Controllers\Stat();
$myrepo = new \Controllers\Repo\Repo();
$reloadableTableOffset = 0;
$envId = __ACTUAL_URI__[2];
$date  = DATE_YMD;

/**
 *  Retrieve repo infos from DB
 */
$myrepo->getAllById('', '', $envId);

/**
 *  Retrieve date from cookie if exists
 */
if (!empty($_COOKIE['tables/stats/ip-access/date'])) {
    $date = $_COOKIE['tables/stats/ip-access/date'];
}

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/stats/ip-access/offset']) and is_numeric($_COOKIE['tables/stats/ip-access/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/stats/ip-access/offset'];
}

/**
 *  Retrieve last access logs, with offset
 */
if ($myrepo->getPackageType() == 'rpm') {
    $reloadableTableContent = $mystats->getAccessIpCount('rpm', $myrepo->getName(), '', '', $myrepo->getEnv(), $date, true, $reloadableTableOffset);
}
if ($myrepo->getPackageType() == 'deb') {
    $reloadableTableContent = $mystats->getAccessIpCount('deb', $myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv(), $date, true, $reloadableTableOffset);
}

/**
 *  Retrieve last access logs, without offset, for the total count
 */
if ($myrepo->getPackageType() == 'rpm') {
    $reloadableTableTotalItems = count($mystats->getAccessIpCount('rpm', $myrepo->getName(), '', '', $myrepo->getEnv(), $date));
}
if ($myrepo->getPackageType() == 'deb') {
    $reloadableTableTotalItems = count($mystats->getAccessIpCount('deb', $myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv(), $date));
}

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($myrepo, $mystats);
