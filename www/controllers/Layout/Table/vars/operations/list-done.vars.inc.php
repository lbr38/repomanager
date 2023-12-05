<?php
$myop = new \Controllers\Operation\Operation();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/operations/list-done/offset']) and is_numeric($_COOKIE['tables/operations/list-done/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/operations/list-done/offset'];
}

/**
 *  Get list of done operations, with offset
 */
$reloadableTableContent = $myop->listDone('', '', true, $reloadableTableOffset);

/**
 *  Get list of ALL done operations, without offset, for the total count
 */
$reloadableTableTotalItems = count($myop->listDone());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

// unset($myop);
