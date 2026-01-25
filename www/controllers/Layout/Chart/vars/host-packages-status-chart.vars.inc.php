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
$hostController = new \Controllers\Host();
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

// First create a list of dates on a 15days period
$dateStart = date_create(date('Y-m-d'))->modify('-15 days')->format('Y-m-d');
$dateEnd = date_create(date('Y-m-d'))->modify('+1 days')->format('Y-m-d');
$period = new DatePeriod(
    new DateTime($dateStart),
    new DateInterval('P1D'),
    new DateTime($dateEnd)
);

// Generate labels array from the date period
foreach ($period as $value) {
    $labels[] = $value->format('Y-m-d');
}

// Getting last 15days installed packages
$lastInstalledPackagesArray = $hostPackageController->countByStatusOverDays('installed', '15');

// Getting last 15days updated packages
$lastUpgradedPackagesArray = $hostPackageController->countByStatusOverDays('upgraded', '15');

// Getting last 15days deleted packages
$lastRemovedPackagesArray = $hostPackageController->countByStatusOverDays('removed', '15');

// Fill datasets with data from arrays (no need for array_merge)
foreach ($labels as $date) {
    $datasets[0]['data'][] = $lastInstalledPackagesArray[$date] ?? 0;
    $datasets[1]['data'][] = $lastUpgradedPackagesArray[$date] ?? 0;
    $datasets[2]['data'][] = $lastRemovedPackagesArray[$date] ?? 0;
}

$options['legend']['show'] = true;
$options['title']['text'] = 'Packages';

unset($hostController, $hostPackageController, $period, $dateStart, $dateEnd, $dates, $lastInstalledPackagesArray, $lastUpgradedPackagesArray, $lastRemovedPackagesArray, $date, $key, $value);
