<?php
$userController = new \Controllers\User\User();
$historyController = new \Controllers\History\History();
$reloadableTableOffset = 0;
$reloadableTablePerPage = 25;
$categories = \Controllers\History\History::CATEGORIES;
$filters = [];

// Getting all usernames
$users = $userController->getUsers();

// Retrieve filters from cookies if exist
if (!empty($_COOKIE['tables/history/list/user']) and is_numeric($_COOKIE['tables/history/list/user'])) {
    $filters['user'] = (int) $_COOKIE['tables/history/list/user'];
}

if (!empty($_COOKIE['tables/history/list/category']) and array_key_exists($_COOKIE['tables/history/list/category'], $categories)) {
    $filters['category'] = $_COOKIE['tables/history/list/category'];
}

if (!empty($_COOKIE['tables/history/list/state']) and in_array($_COOKIE['tables/history/list/state'], ['success', 'error'])) {
    $filters['state'] = $_COOKIE['tables/history/list/state'];
}

if (!empty($_COOKIE['tables/history/list/search']) and is_string($_COOKIE['tables/history/list/search'])) {
    $filters['search'] = mb_substr(trim($_COOKIE['tables/history/list/search']), 0, 100);
}

// Retrieve offset from cookie if exists
if (!empty($_COOKIE['tables/history/list/offset']) and is_numeric($_COOKIE['tables/history/list/offset'])) {
    $reloadableTableOffset = (int) $_COOKIE['tables/history/list/offset'];
}

// Count successful and failed actions matching the other filters, for the status filter labels
$successfulCount = $historyController->count(array_merge($filters, ['state' => 'success']));
$failedCount = $historyController->count(array_merge($filters, ['state' => 'error']));

// Retrieve history, with offset, and the total count for the pagination
$reloadableTableTotalItems = $historyController->count($filters);

// Go back to the first page if the offset is out of range (e.g. filters changed)
if ($reloadableTableOffset >= $reloadableTableTotalItems) {
    $reloadableTableOffset = 0;
}

$reloadableTableContent = $historyController->get($filters, $reloadableTablePerPage, $reloadableTableOffset);

// Count total pages for the pagination
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / $reloadableTablePerPage);

// Calculate current page number
$reloadableTableCurrentPage = floor($reloadableTableOffset / $reloadableTablePerPage) + 1;

unset($userController, $historyController);
