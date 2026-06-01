<?php
$myTask = new \Controllers\Task\Task();
$taskListingController = new \Controllers\Task\Listing();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/tasks/list-running/offset']) and is_numeric($_COOKIE['tables/tasks/list-running/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/tasks/list-running/offset'];
}

/**
 *  Get list of running tasks, with offset
 */
$reloadableTableContent = $taskListingController->getRunning('', true, $reloadableTableOffset);

/**
 *  Get list of ALL running tasks, without offset, for the total count
 */
$reloadableTableTotalItems = count($taskListingController->getRunning());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;
