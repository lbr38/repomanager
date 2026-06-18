<?php
$hostListingController = new \Controllers\Host\Listing();

// Get total number of hosts
$totalHosts = count($hostListingController->get());

// Get total number of up-to-date hosts
$totalUpToDateHosts = count($hostListingController->getUpToDate());

// Calculate the percentage of up-to-date hosts
$upToDate = $totalHosts > 0 ? round(($totalUpToDateHosts / $totalHosts) * 100, 2) : 0;
$value = $upToDate . '%';

if ($upToDate == 100) {
    $options['icon'] = 'check.svg';
} elseif ($upToDate >= 80) {
    $options['icon'] = 'warning.svg';
} else {
    $options['icon'] = 'warning-red.svg';
}

unset($hostListingController);
