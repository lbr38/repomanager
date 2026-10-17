<?php
use \Controllers\User\Permission\Host as HostPermission;
use \Controllers\Host\Host;
use JsonException;

if (!HostPermission::allowedAction('edit-settings')) {
    throw new Exception('You are not allowed to access this panel');
}

$hostController = new Host();

// Get hosts compliance settings
$settings = $hostController->getSettings();

// Decode compliance exclude packages
try {
    $settings['compliance_exclude_packages'] = json_decode($settings['compliance_exclude_packages'], true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    throw new Exception('Failed to decode compliance exclude packages: ' . $e->getMessage());
}

unset($hostController);
