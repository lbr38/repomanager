<?php
$myrepo = new \Controllers\Repo\Repo();
$myplan = new \Controllers\Planification();
$mygroup = new \Controllers\Group('repo');
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/planifications/queued-running/offset']) and is_numeric($_COOKIE['tables/planifications/queued-running/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/planifications/queued-running/offset'];
}

/**
 *  Get list of queued, running and disabled planifications, with offset
 */
$reloadableTableContent = $myplan->getByStatus(array('running', 'queued', 'disabled'), true, $reloadableTableOffset);

/**
 *  Get list of queued, running and disabled planifications, without offset, for the total count
 */
$reloadableTableTotalItems = count($myplan->getByStatus(array('running', 'queued', 'disabled')));

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

// unset($myplan);
