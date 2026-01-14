<?php
$hostController = new \Controllers\Host();
$datasets = [];
$labels = [];
$options = [];
$totalNotUptodate = 0;
$totalUptodate = 0;

// Getting hosts list
$hosts = $hostController->listAll();

// Getting general hosts threshold settings
$hostsSettings = $hostController->getSettings();

// Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
$packagesCountConsideredOutdated = $hostsSettings['pkgs_count_considered_outdated'];

// Loop through the list of hosts to determine the number of hosts up to date and not up to date
foreach ($hosts as $host) {
    // Open the dedicated database of the host from its ID to be able to retrieve additional information
    $hostPackageController = new \Controllers\Host\Package\Package($host['Id']);

    // Retrieve the total number of available packages
    $packagesAvailableTotal = count($hostPackageController->getAvailable());

    // Retrieve the total number of installed packages
    $packagesInstalledTotal = count($hostPackageController->getInstalled());

    /**
     *  If the total number of available packages retrieved previously is > $packagesCountConsideredOutdated (threshold defined by the user) then we increment $totalNotUptodate (counts the number of hosts that are not up to date in the chartjs)
     *  Else it's $totalUptodate that we increment.
     */
    if ($packagesAvailableTotal >= $packagesCountConsideredOutdated) {
        $totalNotUptodate++;
    } else {
        $totalUptodate++;
    }
}

$labels[] = 'Up to date';
$labels[] = 'Need update';
$datasets[0]['data'][] = $totalUptodate;
$datasets[0]['data'][] = $totalNotUptodate;
$datasets[0]['colors'] = ['#24d794', '#F32F63'];

unset($hostController, $hostPackageController, $hosts, $hostsSettings, $totalUptodate, $totalNotUptodate, $packagesCountConsideredOutdated, $packagesAvailableTotal, $packagesInstalledTotal);
