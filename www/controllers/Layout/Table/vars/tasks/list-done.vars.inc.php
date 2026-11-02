<?php
$taskController = new \Controllers\Task\Task();
$taskListingController = new \Controllers\Task\Listing();
$reloadableTableOffset = 0;
$statusFilter = '';

// Retrieve offset from cookie if exists
if (!empty($_COOKIE['tables/tasks/list-done/offset']) and is_numeric($_COOKIE['tables/tasks/list-done/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/tasks/list-done/offset'];
}

// Retrieve status filter from cookie if exists ('successful' or 'failed')
if (!empty($_COOKIE['tables/tasks/list-done/status']) and in_array($_COOKIE['tables/tasks/list-done/status'], ['successful', 'failed'])) {
    $statusFilter = $_COOKIE['tables/tasks/list-done/status'];
}

// Get list of done tasks, with offset
$reloadableTableContent = $taskListingController->getDone('', true, $reloadableTableOffset, $statusFilter);

// Get list of ALL done tasks, without offset, for the total count
$reloadableTableTotalItems = count($taskListingController->getDone('', false, 0, $statusFilter));

// Count total pages for the pagination
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

// Calculate current page number
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

// Get successful tasks count
$successfulCount = $taskListingController->getSuccessful(true);

// Get failed tasks count
$failedCount = $taskListingController->getFailed(true);
