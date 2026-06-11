<?php
use \Controllers\Utils\Generate\Html\Color;

$hostListingController = new \Controllers\Host\Listing();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts agent release version
$agents = $hostListingController->getAgentVersion();

foreach ($agents as $agent) {
    if (empty($agent['Agent_version'])) {
        $labels[] = 'Unknown';
    } else {
        $labels[] = $agent['Agent_version'];
    }

    $datasets[0]['data'][] = $agent['Agent_version_count'];
    $datasets[0]['colors'][] = Color::random();
}

unset($hostController, $agents, $agent, $version);
