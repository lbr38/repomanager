<?php
$myusers = new \Controllers\Login();
$myhistory = new \Controllers\History();
$reloadableTableOffset = 0;
$userId = null;

/**
 *  Getting all usernames
 */
$users = $myusers->getUsers();

/**
 *  Retrieve user Id from cookie if exists
 */
if (!empty($_COOKIE['tables/history/list/id']) and is_numeric($_COOKIE['tables/history/list/id'])) {
    $userId = $_COOKIE['tables/history/list/id'];
}

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/history/list/offset']) and is_numeric($_COOKIE['tables/history/list/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/history/list/offset'];
}

/**
 *  Retrieve history, with offset
 */
if (!empty($userId)) {
    $reloadableTableContent = $myhistory->getByUserId($userId, true, $reloadableTableOffset);
} else {
    $reloadableTableContent = $myhistory->getAll(true, $reloadableTableOffset);
}

/**
 *  Retrieve history, without offset, for the total count
 */
if (!empty($userId)) {
    $reloadableTableTotalItems = count($myhistory->getByUserId($userId));
} else {
    $reloadableTableTotalItems = count($myhistory->getAll());
}

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($myusers, $myhistory);
