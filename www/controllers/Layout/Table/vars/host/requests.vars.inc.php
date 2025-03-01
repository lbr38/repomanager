<?php
$hostRequestController = new \Controllers\Host\Request();
$reloadableTableOffset = 0;

$id = __ACTUAL_URI__[2];

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/host/requests/offset']) and is_numeric($_COOKIE['tables/host/requests/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/host/requests/offset'];
}

/**
 *  Get list of requests sent to the host, with offset
 */
$reloadableTableContent = $hostRequestController->getByHostId($id, true, $reloadableTableOffset);

/**
 *  Get list of ALL requests sent to the host, without offset, for the total count
 */
$reloadableTableTotalItems = count($hostRequestController->getByHostId($id));

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;
