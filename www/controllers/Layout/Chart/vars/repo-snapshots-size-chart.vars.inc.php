<?php
$repoController = new \Controllers\Repo\Repo();
$repoStatsController = new \Controllers\Repo\Statistic\Statistic();
$datasets = [];
$labels = [];
$options = [];

if (__ACTUAL_URI__[2] != 'repo') {
    throw new Exception('Error: invalid URI specified.');
}

if (empty(__ACTUAL_URI__[3])) {
    throw new Exception('Error: missing repository ID.');
}

if (!is_numeric(__ACTUAL_URI__[3])) {
    throw new Exception('Error: invalid repository ID specified.');
}

// Get repository info
$repoController->getAllById(__ACTUAL_URI__[3]);

// Get all statistics for the specified repo ID
$stats = $repoStatsController->getByRepoId(__ACTUAL_URI__[3]);

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
