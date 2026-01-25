<?php
$statsController = new \Controllers\Stat();
$datasets = [];
$labels = [];
$options = [];

if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no snapshot env ID specified');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid snapshot env ID');
}

// Get stats for the last 60 days
$envSizeStats = $statsController->getEnvSize(__ACTUAL_URI__[2], 60);

foreach ($envSizeStats as $stat) {
    $labels[] = $stat['Date'];

    // Convert bytes to MB
    $datasets[0]['data'][] = round(round($stat['Size'] / 1024) / 1024);
}

unset($statsController, $envSizeStats);
