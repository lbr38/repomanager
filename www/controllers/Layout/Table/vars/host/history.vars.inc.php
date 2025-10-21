<?php
$reloadableTableOffset = 0;
$data = [];

$id = __ACTUAL_URI__[2];

$hostPackageController = new \Controllers\Host\Package\Package($id);
$hostPackageEventController = new \Controllers\Host\Package\Event($id);

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/host/history/offset']) and is_numeric($_COOKIE['tables/host/history/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/host/history/offset'];
}

/**
 *  Get list of all events date, with offset
 */
$dates = $hostPackageEventController->getDates(true, $reloadableTableOffset);

/**
 *  For each date, get the packages installed, updated, downgraded, etc.
 */
foreach ($dates as $date) {
    $data[$date]['installed'] = $hostPackageController->getByDate($date, 'installed');
    $data[$date]['reinstalled'] = $hostPackageController->getByDate($date, 'reinstalled');
    $data[$date]['dep-installed'] = $hostPackageController->getByDate($date, 'dep-installed');
    $data[$date]['upgraded'] = $hostPackageController->getByDate($date, 'upgraded');
    $data[$date]['downgraded'] = $hostPackageController->getByDate($date, 'downgraded');
    $data[$date]['removed'] = $hostPackageController->getByDate($date, 'removed');
    $data[$date]['purged'] = $hostPackageController->getByDate($date, 'purged');    
}

$reloadableTableContent = $data;

/**
 *  Get list of ALL events dates, without offset, for the total count
 */
$reloadableTableTotalItems = count($hostPackageEventController->getDates());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($hostPackageController, $hostPackageEventController, $events, $eventsWithPackages, $dates, $data);
