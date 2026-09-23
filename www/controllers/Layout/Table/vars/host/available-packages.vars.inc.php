<?php
use \Controllers\Utils\Convert;

$id = __ACTUAL_URI__[2];
$hostController = new \Controllers\Host\Host();
$hostPackageController = new \Controllers\Host\Package\Package($id);
$hostRequestController = new \Controllers\Host\Request();
$profileController = new \Controllers\Profile();
$reloadableTableOffset = 0;
$packageExcluded = [];
$packageExcludedMajor = [];
$securityUpdates = 0;
$regularUpdates = 0;

// Retrieve offset from cookie if exists
if (!empty($_COOKIE['tables/host/available-packages/offset']) and is_numeric($_COOKIE['tables/host/available-packages/offset'])) {
    $reloadableTableOffset = $_COOKIE['tables/host/available-packages/offset'];
}

// Get host informations
$host = $hostController->get($id);

// Get list of available packages updates with offset
$reloadableTableContent = $hostPackageController->getAvailable(true, $reloadableTableOffset);

$availablePackages = $hostPackageController->getAvailable();

// Get list of ALL available packages updates, without offset, for the total count
$reloadableTableTotalItems = count($availablePackages);

// Count total pages for the pagination
$reloadableTableTotalPages = ceil($reloadableTableTotalItems / 10);

// Calculate current page number
$reloadableTableCurrentPage = ceil($reloadableTableOffset / 10) + 1;

// Check if there is a package update running
$packageUpdateRunning = $hostRequestController->isPackageUpdateRequestRunning($id);

/**
 *  If the host has non-empty informations (the host is not a new host or is not resetted)
 *  Then retrieve the profile configuration
 */
if (!empty($host['Profile'])) {
    // Get the host profile configuration to retrieve the list of packages that are excluded
    try {
        $profileConfiguration = $profileController->getProfileConfiguration($host['Profile']);

        // Get the list of packages that are excluded from updates
        $packageExcluded = array_filter(explode(',', $profileConfiguration['Package_exclude']));

        // Get the list of packages that are excluded from major updates
        $packageExcludedMajor = array_filter(explode(',', $profileConfiguration['Package_exclude_major']));
    } catch (Exception $e) {
        // intentionally ignored
    }
}

// Count security and regular updates
foreach ($availablePackages as $item) {
    if (Convert::toBool($item['Security'] ?? false)) {
        $securityUpdates++;
    } else {
        $regularUpdates++;
    }
}

/**
 *  If a cookie exists with the selected packages, load it
 *  This is used to keep the selected packages after a table page change
 */
if (!empty($_COOKIE['tables/host/available-packages/selected'])) {
    try {
        $selectedPackages = json_decode($_COOKIE['tables/host/available-packages/selected'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('Failed to decode selected packages from cookie: ' . $e->getMessage());
    }
}

unset($hostController, $hostPackageController, $hostRequestController, $profileController, $host, $profileConfiguration, $availablePackages);
