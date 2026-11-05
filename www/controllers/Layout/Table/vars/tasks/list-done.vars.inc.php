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

/**
 *  Retrieve search from cookie if exists (search bar state, searched text, target type and action)
 *  The search is stored in a cookie so that it is kept when the tasks list is reloaded
 */
$doneSearch = $taskListingController->getDoneSearch($_COOKIE['tables/tasks/list-done/search'] ?? '');
$doneSearchActive = ($doneSearch['query'] !== '' or $doneSearch['domain'] !== '' or $doneSearch['action'] !== '');

// Get list of done tasks, with offset
$reloadableTableContent = $taskListingController->getDone('', true, $reloadableTableOffset, $statusFilter, $doneSearch['criteria']);

// Count all done tasks, for the pagination
$reloadableTableTotalItems = $taskListingController->countDone($statusFilter, $doneSearch['criteria']);

// Count total pages for the pagination
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

// Calculate current page number
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

// Get successful and failed tasks count (matching the search, if any)
$successfulCount = $taskListingController->countDone('successful', $doneSearch['criteria']);
$failedCount = $taskListingController->countDone('failed', $doneSearch['criteria']);
