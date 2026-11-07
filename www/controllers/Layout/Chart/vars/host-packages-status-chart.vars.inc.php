<?php
// Check that a host ID is specified
if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no host ID specified');
}

// Check that the host ID is numeric
if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid host ID');
}

$id = __ACTUAL_URI__[2];
$hostController = new \Controllers\Host\Host();
$hostPackageController = new \Controllers\Host\Package\Package($id);
$labels = [];
$options = [];
$datasets = [
    [
        'name' => 'Installed',
        'data' => [],
        'color' => '#4CAF50' // Green
    ],
    [
        'name' => 'Upgraded',
        'data' => [],
        'color' => '#FFC107' // Amber
    ],
    [
        'name' => 'Removed',
        'data' => [],
        'color' => '#F44336' // Red
    ]
];

// First create a list of dates over the selected period (without going beyond today)
$dateStart = date('Y-m-d', $timeStart);
$dateEnd = date('Y-m-d', min($timeEnd, time()));
$period = new DatePeriod(
    new DateTime($dateStart),
    new DateInterval('P1D'),
    date_create($dateEnd)->modify('+1 days')
);

// Generate labels array from the date period
foreach ($period as $value) {
    $labels[] = $value->format('Y-m-d');
}

// Getting installed packages over the period
$lastInstalledPackagesArray = $hostPackageController->countByStatusOverPeriod('installed', $dateStart, $dateEnd);

// Getting updated packages over the period
$lastUpgradedPackagesArray = $hostPackageController->countByStatusOverPeriod('upgraded', $dateStart, $dateEnd);

// Getting deleted packages over the period
$lastRemovedPackagesArray = $hostPackageController->countByStatusOverPeriod('removed', $dateStart, $dateEnd);

// Fill datasets with data from arrays (no need for array_merge)
foreach ($labels as $date) {
    $datasets[0]['data'][] = $lastInstalledPackagesArray[$date] ?? 0;
    $datasets[1]['data'][] = $lastUpgradedPackagesArray[$date] ?? 0;
    $datasets[2]['data'][] = $lastRemovedPackagesArray[$date] ?? 0;
}

$options['legend']['show'] = true;

unset($hostController, $hostPackageController, $period, $dateStart, $dateEnd, $dates, $lastInstalledPackagesArray, $lastUpgradedPackagesArray, $lastRemovedPackagesArray, $date, $key, $value);
