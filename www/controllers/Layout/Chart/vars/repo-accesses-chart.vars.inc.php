<?php
$repoController = new \Controllers\Repo\Repo();
$repoEnvController = new \Controllers\Repo\Environment();
$envController = new \Controllers\Environment();
$debRepoStatController = new \Controllers\Repo\Statistic\Deb();
$rpmRepoStatController = new \Controllers\Repo\Statistic\Rpm();
$datasets = [];
$labels = [];
$options = [];
$envs = [];
$envData = [];

// Mirror page (/repository/mirror/<id>)
$isMirror = (__ACTUAL_URI__[2] ?? '') == 'mirror';

// Check that repository Id is specified
if (!$isMirror && empty(__ACTUAL_URI__[2])) {
    throw new Exception('no repository ID specified');
}

// Check that repository Id is valid
if (!$isMirror && !is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid repository ID');
}

// Retrieve environments from cookie if exists
if (!empty($_COOKIE['chart/stats/accesses/envs'])) {
    $envs = json_decode($_COOKIE['chart/stats/accesses/envs'], true);
}

// Get statistics directly from database for all environments
$results = [];

if ($isMirror) {
    $mirror = (new \Controllers\Mirror\Mirror())->getForPage(__ACTUAL_URI__[3] ?? '');
    $results = (new \Controllers\Mirror\Statistic())->getAccessByPeriod($mirror['Id'], $envs, $timeStart, $timeEnd);
} else {
    // Retrieve repo infos from DB
    $repoController->getAllById(__ACTUAL_URI__[2], '', '');
}

if (!$isMirror && $repoController->getPackageType() == 'rpm') {
    $results = $rpmRepoStatController->getAccessByPeriod($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd);
}
if (!$isMirror && $repoController->getPackageType() == 'deb') {
    $results = $debRepoStatController->getAccessByPeriod($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd);
}

if (!empty($results)) {
    /**
     *  Group accesses into intervals, so that the chart has a regular number of points whatever the period, with 0 for intervals without access
     *  Use the smallest interval giving at most 1000 points over the period
     */
    $intervals = [
        60 => '1 minute',
        300 => '5 minutes',
        600 => '10 minutes',
        900 => '15 minutes',
        1800 => '30 minutes',
        3600 => '1 hour',
        10800 => '3 hours',
        21600 => '6 hours',
        43200 => '12 hours',
        86400 => '1 day',
        604800 => '1 week'
    ];

    foreach ($intervals as $interval => $intervalName) {
        if (($timeEnd - $timeStart) / $interval <= 1000) {
            break;
        }
    }

    // Intervals are aligned on the server current local time (e.g. daily intervals start at midnight)
    $offset = (int) date('Z');

    // Group results by environment and interval
    foreach ($results as $row) {
        $env = $row['Env'];
        $intervalStart = intdiv((int) $row['Timestamp'] + $offset, $interval) * $interval - $offset;

        // Initialize environment data if not exists
        if (!isset($envData[$env])) {
            $envData[$env] = [
                'name' => $env,
                'counts' => [],
                'color' => \Controllers\Environment::getEnvColor($env)
            ];
        }

        $envData[$env]['counts'][$intervalStart] = ($envData[$env]['counts'][$intervalStart] ?? 0) + $row['Count'];
    }

    // Generate labels: start of each interval of the period, without going beyond the current time
    $periodEnd = min($timeEnd, time());

    for ($intervalStart = intdiv($timeStart + $offset, $interval) * $interval - $offset; $intervalStart <= $periodEnd; $intervalStart += $interval) {
        $labels[] = $intervalStart;
    }

    // Build the final datasets
    $datasets = array_values(array_map(function ($env) use ($labels) {
        return [
            'name' => $env['name'],
            'data' => array_map(fn($intervalStart) => $env['counts'][$intervalStart] ?? 0, $labels),
            'color' => $env['color']
        ];
    }, $envData));

    // Labels in milliseconds for the chart
    $labels = array_map(fn($intervalStart) => $intervalStart * 1000, $labels);

    // Calculate totals for each timestamp and add Total dataset
    // $totalData = [];
    // foreach ($labels as $timestamp) {
    //     $total = 0;
    //     foreach ($envData as $env) {
    //         $total += $env['timestamps'][$timestamp] ?? 0;
    //     }
    //     $totalData[] = $total;
    // }

    // // Add Total dataset with green color
    // $datasets[] = [
    //     'name' => 'Total',
    //     'data' => $totalData,
    //     'color' => '#15bf7f'
    // ];

    // Configure tooltip options with axis pointer for line charts, and display the interval in the title
    $options = [
        'title' => [
            'text' => 'Accesses per ' . $intervalName
        ],
        'tooltip' => [
            'trigger' => 'axis',
            'axisPointer' => [
                'type' => 'line',
                'animation' => false,
                'label' => [
                    'backgroundColor' => '#505765'
                ]
            ]
        ]
    ];
}

unset($repoController, $repoEnvController, $debRepoStatController, $rpmRepoStatController, $envData, $results, $intervals, $interval, $intervalName, $intervalStart, $offset, $periodEnd, $row, $env);
