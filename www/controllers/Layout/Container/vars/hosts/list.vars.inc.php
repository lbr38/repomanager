<?php
$groupController = new \Controllers\Group\Host();
$hostController = new \Controllers\Host\Host();
$hostListingController = new \Controllers\Host\Listing();
$hostRequestController = new \Controllers\Host\Request();
$compactView = true;

// Get total hosts
$totalHosts = count($hostListingController->get());

// Get hosts groups list
$hostGroupsList = $groupController->listAll(true);

// Get general hosts settings
$settings = $hostController->getSettings();

// Threshold of the maximum number of available update above which the host is considered as 'not compliant'
$complianceThresholdCount = $settings['compliance_threshold_count'];

// Threshold of the latest update age in days above which the host is considered as 'not compliant'
$complianceThresholdDays = $settings['compliance_threshold_days'];

if (isset($_COOKIE['hosts/compact-view']) and $_COOKIE['hosts/compact-view'] == false) {
    $compactView = false;
}

// Layout variables depending on the view mode
if ($compactView) {
    $layoutPackagesTitle = 'PKG.';
    $layoutGridClass = 'grid-5';
} else {
    $layoutPackagesTitle = 'PACKAGES';
    $layoutGridClass = 'grid-4';
}

unset($groupController, $settings);
