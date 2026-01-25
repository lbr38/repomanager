<?php
$hostController = new \Controllers\Host();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts agent status
$agents = $hostController->listCountAgentStatus();

if (!empty($agents['Linupdate_agent_status_online_count'])) {
    $labels[] = 'Online';
    $datasets[0]['data'][] = $agents['Linupdate_agent_status_online_count'];
    $datasets[0]['colors'][] = '#24d794';
}

if (!empty($agents['Linupdate_agent_status_seems_stopped_count'])) {
    $labels[] = 'Seems stopped';
    $datasets[0]['data'][] = $agents['Linupdate_agent_status_seems_stopped_count'];
    $datasets[0]['colors'][] = '#e0b05f';
}

if (!empty($agents['Linupdate_agent_status_stopped_count'])) {
    $labels[] = 'Stopped';
    $datasets[0]['data'][] = $agents['Linupdate_agent_status_stopped_count'];
    $datasets[0]['colors'][] = 'rgb(255, 99, 132)';
}

if (!empty($agents['Linupdate_agent_status_disabled_count'])) {
    $labels[] = 'Disabled';
    $datasets[0]['data'][] = $agents['Linupdate_agent_status_disabled_count'];
    $datasets[0]['colors'][] = 'rgb(255, 99, 132)';
}

unset($hostController, $agents);
