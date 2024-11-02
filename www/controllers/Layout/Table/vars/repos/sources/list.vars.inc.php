<?php
$mysource = new \Controllers\Repo\Source\Source();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/repos/sources/list/offset']) and is_numeric($_COOKIE['tables/repos/sources/list/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/repos/sources/list/offset'];
}

/**
 *  Get list of source repos, with offset
 */
$reloadableTableContent = $mysource->listAll('', true, $reloadableTableOffset);

/**
 *  Get list of source repos, without offset, for the total count
 */
$reloadableTableTotalItems = count($mysource->listAll(''));

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($mysource);
