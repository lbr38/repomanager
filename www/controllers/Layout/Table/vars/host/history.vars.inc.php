<?php
$myhost = new \Controllers\Host();
$reloadableTableOffset = 0;

$id = __ACTUAL_URI__[2];

/**
 *  Open host database
 */
$myhost->openHostDb($id);

/**
 *  Retrieve offset from cookie if exists
 */
if (!empty($_COOKIE['tables/host/history/offset']) and is_numeric($_COOKIE['tables/host/history/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/host/history/offset'];
}

/**
 *  Get list of packages events history, with offset
 */
$events = $myhost->getEventsHistory(true, $reloadableTableOffset);

/**
 *  For each event, get the installed, updated, downgraded and removed packages
 */
$eventsWithPackages = [];

foreach ($events as $event) {
    /**
     *  Getting installed packages from this event
     */
    $event['PackagesInstalled'] = $myhost->getEventPackagesList($event['Id'], 'installed');

    /**
     *  Getting isntalled dependencies packages from this event
     */
    $event['DependenciesInstalled'] = $myhost->getEventPackagesList($event['Id'], 'dep-installed');

    /**
     *  Getting updated packages from this event
     */
    $event['PackagesUpdated'] = $myhost->getEventPackagesList($event['Id'], 'upgraded');

    /**
     *  Getting downgraded packages from this event
     */
    $event['PackagesDowngraded'] = $myhost->getEventPackagesList($event['Id'], 'downgraded');

    /**
     *  Getting removed packages from this event
     */
    $event['PackagesRemoved'] = $myhost->getEventPackagesList($event['Id'], 'removed');

    /**
     *  Getting purged packages from this event
     */
    $event['PackagesPurged'] = $myhost->getEventPackagesList($event['Id'], 'purged');

    /**
     *  Add this event to the list of events with packages
     */
    $eventsWithPackages[] = $event;
}

$reloadableTableContent = $eventsWithPackages;

/**
 *  Get list of ALL events, without offset, for the total count
 */
$reloadableTableTotalItems = count($myhost->getEventsHistory());

/**
 *  Count total pages for the pagination
 */
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

/**
 *  Calculate current page number
 */
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

/**
 *  Close host database
 */
$myhost->closeHostDb();

unset($myhost, $events, $eventsWithPackages);
