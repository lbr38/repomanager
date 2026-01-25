<?php
$systemMonitoringController = new \Controllers\System\Monitoring\Monitoring();
$datasets = [];
$labels = [];
$options = [];

/**
 *  Get disk usage data
 */
$memoryUsageStats = $systemMonitoringController->get($timeStart, $timeEnd);

foreach ($memoryUsageStats as $stat) {
    $labels[] = $stat['Timestamp'] * 1000;
    $datasets[0]['data'][] = $stat['Disk_usage'];
}

/**
 *  Add current disk usage to the list
 */
$labels[] = time() * 1000;
$datasets[0]['data'][] = \Controllers\System\Monitoring\Disk::getUsage('/');

/**
 *  Prepare chart data
 */
$options['title']['text'] = 'Disk usage (%)';
$options['init-zoom'] = 60;
$datasets[0]['color'] = '#F32F63';
$datasets[0]['name'] = 'Disk usage';

unset($systemMonitoringController, $memoryUsageStats, $stat);
