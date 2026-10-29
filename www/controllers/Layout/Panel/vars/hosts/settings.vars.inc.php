<?php
use \Controllers\User\Permission\Host as HostPermission;
use \Controllers\Host\Host;

if (!HostPermission::allowedAction('edit-settings')) {
    throw new Exception('You are not allowed to access this panel');
}

$hostController = new Host();

// Get hosts compliance settings
$settings = $hostController->getSettings();

unset($hostController);
