<?php
$id = __ACTUAL_URI__[2];
$hostPackageController = new \Controllers\Host\Package\Package($id);
$reloadableTableOffset = 0;
$search = '';

// Retrieve offset from cookie if exists
if (!empty($_COOKIE['tables/host/installed-packages/offset']) and is_numeric($_COOKIE['tables/host/installed-packages/offset'])) {
    $reloadableTableOffset = (int) $_COOKIE['tables/host/installed-packages/offset'];
}

// Retrieve search filter from cookie if exists
if (!empty($_COOKIE['tables/host/installed-packages/search'])) {
    $search = \Controllers\Utils\Validate::string($_COOKIE['tables/host/installed-packages/search']);
}

// Get total count of inventoried packages (filtered if a search is active)
$reloadableTableTotalItems = $hostPackageController->countInventory($search);

// If offset is out of range, reset it to 0
if ($reloadableTableOffset >= $reloadableTableTotalItems) {
    $reloadableTableOffset = 0;
}

// Get list of inventoried packages, with offset and optional search filter
$reloadableTableContent = $hostPackageController->getInventory($search, true, $reloadableTableOffset);

// Count total pages for the pagination
$reloadableTableTotalPages = (int) ceil($reloadableTableTotalItems / 10);

// Calculate current page number
$reloadableTableCurrentPage = (int) ceil($reloadableTableOffset / 10) + 1;

unset($hostPackageController);
