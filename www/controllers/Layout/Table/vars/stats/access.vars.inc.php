<?php
$mystats = new \Controllers\Stat();
$myrepo = new \Controllers\Repo\Repo();
$reloadableTableOffset = 0;
$envId = __ACTUAL_URI__[2];

/**
 *  Retrieve repo infos from DB
 */
$myrepo->getAllById('', '', $envId);

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/stats/access/offset']) and is_numeric($_COOKIE['tables/stats/access/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/stats/access/offset'];
}

/**
 *  Retrieve last access logs, with offset
 */
if ($myrepo->getPackageType() == 'rpm') {
    $reloadableTableContent = $mystats->getAccess($myrepo->getName(), '', '', $myrepo->getEnv(), true, $reloadableTableOffset);
}
if ($myrepo->getPackageType() == 'deb') {
    $reloadableTableContent = $mystats->getAccess($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv(), true, $reloadableTableOffset);
}

/**
 *  Retrieve last access logs, without offset, for the total count
 */
if ($myrepo->getPackageType() == 'rpm') {
    $reloadableTableTotalItems = count($mystats->getAccess($myrepo->getName(), '', '', $myrepo->getEnv()));
}
if ($myrepo->getPackageType() == 'deb') {
    $reloadableTableTotalItems = count($mystats->getAccess($myrepo->getName(), $myrepo->getDist(), $myrepo->getSection(), $myrepo->getEnv()));
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
