<?php
$myTask = new \Controllers\Task\Task();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/tasks/list-scheduled/offset']) and is_numeric($_COOKIE['tables/tasks/list-scheduled/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/tasks/list-scheduled/offset'];
}

/**
 *  Get list of scheduled tasks, with offset
 */
$reloadableTableContent = $myTask->listScheduled(true, $reloadableTableOffset);

/**
 *  Get list of ALL scheduled tasks, without offset, for the total count
 */
$reloadableTableTotalItems = count($myTask->listScheduled());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;
