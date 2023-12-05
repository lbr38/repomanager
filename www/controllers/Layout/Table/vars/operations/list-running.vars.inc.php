<?php
$myop = new \Controllers\Operation\Operation();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/operations/list-running/offset']) and is_numeric($_COOKIE['tables/operations/list-running/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/operations/list-running/offset'];
}

/**
 *  Get list of running operations, with offset
 */
$reloadableTableContent = $myop->listRunning('', true, $reloadableTableOffset);

/**
 *  Get list of ALL running operations, without offset, for the total count
 */
$reloadableTableTotalItems = count($myop->listRunning());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

// unset($myop);
