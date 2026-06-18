<?php
$id = __ACTUAL_URI__[2];
$hostPackageController = new \Controllers\Host\Package\Package($id);
$hostController = new \Controllers\Host\Host();

// Get hosts settings
$settings = $hostController->getSettings();

// Get compliance threshold count
$complianceThresholdCount = $settings['compliance_threshold_count'];

// Get compliance threshold days
$complianceThresholdDays = $settings['compliance_threshold_days'];

// Get installed packages and its total
$packagesInventored = $hostPackageController->getInventory();
$packagesInstalledCount = count($hostPackageController->getInstalled());

// Get available packages and its total
$packagesAvailableTotal = count($hostPackageController->getAvailable());

unset($hostController, $hostPackageController, $settings);
