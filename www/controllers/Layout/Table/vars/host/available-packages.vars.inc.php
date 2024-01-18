<?php
$myhost = new \Controllers\Host();
$reloadableTableOffset = 0;

$id = __ACTUAL_URI__[2];

/**
 *  Open host database
 */
$myhost->openHostDb($id);

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/host/available-packages/offset']) and is_numeric($_COOKIE['tables/host/available-packages/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/host/available-packages/offset'];
}

/**
 *  Get list of available packages updates, with offset
 */
$reloadableTableContent = $myhost->getPackagesAvailable(true, $reloadableTableOffset);

/**
 *  Get list of ALL available packages updates, without offset, for the total count
 */
$reloadableTableTotalItems = count($myhost->getPackagesAvailable());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

/**
 *  Close host database
 */
$myhost->closeHostDb();

unset($myhost);
