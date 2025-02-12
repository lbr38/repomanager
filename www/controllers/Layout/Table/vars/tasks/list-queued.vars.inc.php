<?php
$myTask = new \Controllers\Task\Task();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/tasks/list-queued/offset']) and is_numeric($_COOKIE['tables/tasks/list-queued/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/tasks/list-queued/offset'];
}

/**
 *  Get list of queued tasks, with offset
 */
$reloadableTableContent = $myTask->listQueued('', true, $reloadableTableOffset);

/**
 *  Get list of ALL queued tasks, without offset, for the total count
 */
$reloadableTableTotalItems = count($myTask->listQueued());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;
