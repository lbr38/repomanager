<?php
$userController = new \Controllers\User\User();
$historyController = new \Controllers\History\History();
$reloadableTableOffset = 0;
$userId = null;

/**
 *  Getting all usernames
 */
$users = $userController->getUsers();

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
    $reloadableTableContent = $historyController->getByUserId($userId, true, $reloadableTableOffset);
} else {
    $reloadableTableContent = $historyController->getAll(true, $reloadableTableOffset);
}

/**
 *  Retrieve history, without offset, for the total count
 */
if (!empty($userId)) {
    $reloadableTableTotalItems = count($historyController->getByUserId($userId));
} else {
    $reloadableTableTotalItems = count($historyController->getAll());
}

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($userController, $historyController);
