<?php
use \Controllers\User\Permission\Host as HostPermission;
use \Controllers\Host\Host;

if (!HostPermission::allowedAction('edit-settings')) {
    throw new Exception('You are not allowed to access this panel');
}

$hostController = new Host();

// Get hosts settings
$settings = $hostController->getSettings();

// Get compliance threshold count
$complianceThresholdCount = $settings['compliance_threshold_count'];

// Get compliance threshold days
$complianceThresholdDays = $settings['compliance_threshold_days'];

// Get compliance reboot required
$complianceRebootRequired = $settings['compliance_reboot_required'];

unset($hostController, $settings);
