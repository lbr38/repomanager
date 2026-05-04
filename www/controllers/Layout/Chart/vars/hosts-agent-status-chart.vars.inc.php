<?php
$hostListingController = new \Controllers\Host\Listing();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts agent status
$agents = $hostListingController->getAgentStatus();

if (!empty($agents['Online_count'])) {
    $labels[] = 'Online';
    $datasets[0]['data'][] = $agents['Online_count'];
    $datasets[0]['colors'][] = '#24d794';
}

if (!empty($agents['Seems_stopped_count'])) {
    $labels[] = 'Seems stopped';
    $datasets[0]['data'][] = $agents['Seems_stopped_count'];
    $datasets[0]['colors'][] = '#e0b05f';
}

if (!empty($agents['Stopped_count'])) {
    $labels[] = 'Stopped';
    $datasets[0]['data'][] = $agents['Stopped_count'];
    $datasets[0]['colors'][] = 'rgb(255, 99, 132)';
}

if (!empty($agents['Disabled_count'])) {
    $labels[] = 'Disabled';
    $datasets[0]['data'][] = $agents['Disabled_count'];
    $datasets[0]['colors'][] = 'rgb(255, 99, 132)';
}

unset($hostListingController, $agents);
