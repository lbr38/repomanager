<?php
$repoController = new \Controllers\Repo\Repo();
$repoStatsController = new \Controllers\Repo\Statistic\Statistic();
$datasets = [];
$labels = [];
$options = [];

// Check that repository Id is specified
if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no repository ID specified');
}

// Check that repository Id is valid
if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid repository ID');
}

// Get repository info
$repoController->getAllById(__ACTUAL_URI__[2]);

// Get all statistics for the specified repo ID
$stats = $repoStatsController->getByRepoId(__ACTUAL_URI__[2]);

$snapData = [];

foreach ($stats as $stat) {
    if (!isset($labels[$stat['Timestamp'] * 1000])) {
        $labels[] = $stat['Timestamp'] * 1000;
    }

    // Store the snapshot size for this timestamp
    $snapData[$stat['Snapshot_date']] = [
        'timestamp' => $stat['Timestamp'] * 1000,
        'size' => $stat['Snapshot_size'],
    ];
}

unset($repoController, $repoStatsController);
