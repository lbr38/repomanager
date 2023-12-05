<?php
$myrepo = new \Controllers\Repo\Repo();
$myplan = new \Controllers\Planification();
$mygroup = new \Controllers\Group('repo');
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/planifications/history/offset']) and is_numeric($_COOKIE['tables/planifications/history/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/planifications/history/offset'];
}

/**
 *  Get list of done planifications, with offset
 */
$reloadableTableContent = $myplan->getByStatus(array('done', 'error', 'stopped'), true, $reloadableTableOffset);

/**
 *  Get list of ALL done planifications, without offset, for the total count
 */
$reloadableTableTotalItems = count($myplan->getByStatus(array('done', 'error', 'stopped')));

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($myplan);
