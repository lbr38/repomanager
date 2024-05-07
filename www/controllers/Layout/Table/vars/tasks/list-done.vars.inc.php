<?php
$myTask = new \Controllers\Task\Task();
$reloadableTableOffset = 0;

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/tasks/list-done/offset']) and is_numeric($_COOKIE['tables/tasks/list-done/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/tasks/list-done/offset'];
}

/**
 *  Get list of done tasks, with offset
 */
$reloadableTableContent = $myTask->listDone('', true, $reloadableTableOffset);

/**
 *  Get list of ALL done tasks, without offset, for the total count
 */
$reloadableTableTotalItems = count($myTask->listDone());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;
