<?php
$systemMonitoringController = new \Controllers\System\Monitoring\Monitoring();
$datasets = [];
$labels = [];
$options = [];

/**
 *  Get memory usage data
 */
$memoryUsageStats = $systemMonitoringController->get($timeStart, $timeEnd);

foreach ($memoryUsageStats as $stat) {
    $labels[] = $stat['Timestamp'] * 1000;
    $datasets[0]['data'][] = $stat['Memory_usage'];
}

/**
 *  Add current memory usage to the list
 */
$labels[] = time() * 1000;
$datasets[0]['data'][] = \Controllers\System\Monitoring\Memory::getUsage();

/**
 *  Prepare chart data
 */
$options['title']['text'] = 'Memory usage (%)';
$options['init-zoom'] = 60;
$datasets[0]['color'] = '#F32F63';
$datasets[0]['name'] = 'Memory usage';

unset($systemMonitoringController, $memoryUsageStats, $stat);
