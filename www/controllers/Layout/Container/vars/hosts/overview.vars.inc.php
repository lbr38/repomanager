<?php
use \Controllers\Host\Package\Package;

$hostController = new \Controllers\Host\Host();
$hostListingController = new \Controllers\Host\Listing();
$totalOutdated = 0;
$totalUptodate = 0;
$compliancePercent = 0;

// Getting hosts list
$hosts = $hostListingController->get();

// Getting total hosts
$totalHosts = count($hosts);

// Getting a list of all host kernels and sort them by count desc
$kernels = $hostListingController->getKernel();
array_multisort(array_column($kernels, 'Count'), SORT_DESC, $kernels);

// Getting a list of all host profiles and sort them by count desc
$profiles = $hostListingController->getProfile();
array_multisort(array_column($profiles, 'Count'), SORT_DESC, $profiles);

// Getting a list of all hosts requiring a reboot
$rebootRequiredList = $hostListingController->getRebootRequired();
$rebootRequiredCount = count($rebootRequiredList);

// Getting general hosts threshold settings
$hostsSettings = $hostController->getSettings();

// Threshold of the maximum number of available update above which the host is considered as 'not up to date' (but not critical)
$packagesCountConsideredOutdated = $hostsSettings['pkgs_count_considered_outdated'];

// Loop through the list of hosts to determine the number of hosts up to date and not up to date
foreach ($hosts as $host) {
    // Open the dedicated database of the host from its ID to be able to retrieve additional information
    $hostPackageController = new Package($host['Id']);

    // Retrieve the total number of available packages
    $packagesAvailableTotal = count($hostPackageController->getAvailable());

    // Retrieve the total number of installed packages
    $packagesInstalledTotal = count($hostPackageController->getInstalled());

    /**
     *  If the total number of available packages retrieved previously is > $packagesCountConsideredOutdated (threshold defined by the user) then we increment $totalOutdated (counts the number of hosts that are not up to date in the chartjs)
     *  Else it's $totalUptodate that we increment.
     */
    if ($packagesAvailableTotal >= $packagesCountConsideredOutdated) {
        $totalOutdated++;
    } else {
        $totalUptodate++;
    }
}

// Calculation of the compliance percent (number of hosts up to date / total number of hosts * 100)
if ($totalHosts > 0) {
    $compliancePercent = round(($totalUptodate / $totalHosts) * 100);
}

unset($hostController, $hostListingController, $hostPackageController, $hosts, $hostsSettings, $packagesCountConsideredOutdated, $packagesAvailableTotal, $packagesInstalledTotal);
