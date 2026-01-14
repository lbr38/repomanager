<?php
$statsController = new \Controllers\Stat();
$datasets = [];
$labels = [];
$options = [];

// Check that env Id is specified
if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no snapshot env ID specified');
}

// Check that env Id is valid
if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid snapshot env ID');
}

// Get stats for the last 60 days
$stats = $statsController->getPkgCount(__ACTUAL_URI__[2], 60);

foreach ($stats as $stat) {
    $labels[] = $stat['Date'];
    $datasets[0]['data'][]= $stat['Packages_count'];
}

unset($statsController, $stats);
