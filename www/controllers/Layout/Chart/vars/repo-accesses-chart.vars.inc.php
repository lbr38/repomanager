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
$allTimestamps = [];
$envData = [];

// Check that repository Id is specified
if (empty(__ACTUAL_URI__[3])) {
    throw new Exception('no repository ID specified');
}

// Check that repository Id is valid
if (!is_numeric(__ACTUAL_URI__[3])) {
    throw new Exception('invalid repository ID');
}

// Retrieve environments from cookie if exists
if (!empty($_COOKIE['chart/stats/accesses/envs'])) {
    $envs = json_decode($_COOKIE['chart/stats/accesses/envs'], true);
}

// Retrieve period from cookie if exists
// TODO: ranges
// if (!empty($_COOKIE['chart/stats/repo-accesses/period'])) {
//     $timeStart = strtotime(explode(' - ', $_COOKIE['chart/stats/repo-accesses/period'])[0]);
//     $timeEnd = strtotime(explode(' - ', $_COOKIE['chart/stats/repo-accesses/period'])[1]);
// }

// Retrieve repo infos from DB
$repoController->getAllById(__ACTUAL_URI__[3], '', '');

// Get statistics directly from database for all environments
$results = [];
if ($repoController->getPackageType() == 'rpm') {
    $results = $rpmRepoStatController->getAccessByPeriod($repoController->getName(), $repoController->getReleasever(), $envs, $timeStart, $timeEnd);
}
if ($repoController->getPackageType() == 'deb') {
    $results = $debRepoStatController->getAccessByPeriod($repoController->getName(), $repoController->getDist(), $repoController->getSection(), $envs, $timeStart, $timeEnd);
}

if (!empty($results)) {
    // Group results by environment
    foreach ($results as $row) {
        $env = $row['Env'];
        $timestamp = $row['Timestamp'] * 1000;
        $count = $row['Count'];

        // Initialize environment data if not exists
        if (!isset($envData[$env])) {
            $envData[$env] = [
                'name' => $env,
                'timestamps' => [],
                'color' => \Controllers\Environment::getEnvColor($env)
            ];
        }

        // Store timestamp data
        $envData[$env]['timestamps'][$timestamp] = $count;

        // Collect unique timestamps
        $allTimestamps[] = $timestamp;
    }

    // Add final point for each environment and convert to indexed array
    $currentTimestamp = time() * 1000;
    $allTimestamps[] = $currentTimestamp;
    $envData = array_values(array_map(function ($env) use ($currentTimestamp) {
        $env['timestamps'][$currentTimestamp] = 0;
        return $env;
    }, $envData));

    // Create unique and sorted labels
    $labels = array_values(array_unique($allTimestamps));
    sort($labels);

    // Build the final datasets using array_map for optimization
    $datasets = array_map(function ($env) use ($labels) {
        return [
            'name' => $env['name'],
            'data' => array_map(fn($timestamp) => $env['timestamps'][$timestamp] ?? 0, $labels),
            'color' => $env['color']
        ];
    }, $envData);

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

    // Configure tooltip options with axis pointer for line charts
    $options = [
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

unset($repoController, $repoEnvController, $debRepoStatController, $rpmRepoStatController, $envData, $allTimestamps, $envTimestamps, $results);
