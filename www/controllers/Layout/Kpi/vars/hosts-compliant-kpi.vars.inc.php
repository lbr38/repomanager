<?php
$hostListingController = new \Controllers\Host\Listing();

// Get total number of hosts
$totalHosts = count($hostListingController->get());

// Get total number of compliant hosts
$totalCompliantHosts = count($hostListingController->getCompliant());

// Calculate the percentage of compliant hosts
$compliance = $totalHosts > 0 ? round(($totalCompliantHosts / $totalHosts) * 100, 2) : 0;
$value = $compliance . '%';

if ($compliance == 100) {
    $options['icon'] = 'check.svg';
} elseif ($compliance >= 80) {
    $options['icon'] = 'warning.svg';
} else {
    $options['icon'] = 'warning-red.svg';
}

unset($hostListingController);
