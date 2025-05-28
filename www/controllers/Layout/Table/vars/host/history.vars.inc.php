<?php
$reloadableTableOffset = 0;

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
 *  Get list of packages events history, with offset
 */
$events = $hostPackageEventController->getHistory(true, $reloadableTableOffset);

/**
 *  For each event, get the installed, updated, downgraded and removed packages
 */
$eventsWithPackages = [];

foreach ($events as $event) {
    /**
     *  Getting installed packages from this event
     */
    $event['PackagesInstalled'] = $hostPackageController->getEventPackagesList($event['Id'], 'installed');

    /**
     *  Getting reinstalled packages from this event
     */
    $event['PackagesReinstalled'] = $hostPackageController->getEventPackagesList($event['Id'], 'reinstalled');

    /**
     *  Getting isntalled dependencies packages from this event
     */
    $event['DependenciesInstalled'] = $hostPackageController->getEventPackagesList($event['Id'], 'dep-installed');

    /**
     *  Getting updated packages from this event
     */
    $event['PackagesUpdated'] = $hostPackageController->getEventPackagesList($event['Id'], 'upgraded');

    /**
     *  Getting downgraded packages from this event
     */
    $event['PackagesDowngraded'] = $hostPackageController->getEventPackagesList($event['Id'], 'downgraded');

    /**
     *  Getting removed packages from this event
     */
    $event['PackagesRemoved'] = $hostPackageController->getEventPackagesList($event['Id'], 'removed');

    /**
     *  Getting purged packages from this event
     */
    $event['PackagesPurged'] = $hostPackageController->getEventPackagesList($event['Id'], 'purged');

    /**
     *  Add this event to the list of events with packages
     */
    $eventsWithPackages[] = $event;
}

$reloadableTableContent = $eventsWithPackages;

/**
 *  Get list of ALL events, without offset, for the total count
 */
$reloadableTableTotalItems = count($hostPackageEventController->getHistory());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

unset($hostPackageController, $hostPackageEventController, $events, $eventsWithPackages);
