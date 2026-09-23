<?php
$id = __ACTUAL_URI__[2];
$hostPackageController = new \Controllers\Host\Package\Package($id);
$hostController = new \Controllers\Host\Host();

// Get hosts settings
$settings = $hostController->getSettings();

// Get compliance threshold count
$complianceThresholdCount = $settings['compliance']['thresholds']['count'];

// Get compliance threshold days
$complianceThresholdDays = $settings['compliance']['thresholds']['days'];

// Get installed packages and its total
$packagesInventoredTotal = $hostPackageController->countInventory();
$packagesInstalledCount = count($hostPackageController->getInstalled());

// Get available packages and its total
$packagesAvailableTotal = count($hostPackageController->getAvailable());

unset($hostController, $hostPackageController, $settings);
