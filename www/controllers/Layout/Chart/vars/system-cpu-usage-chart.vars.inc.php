<?php
$systemMonitoringController = new \Controllers\System\Monitoring\Monitoring();
$datasets = [];
$labels = [];
$options = [];

/**
 *  Get CPU usage data
 *  This will fetch the last 60 minutes of CPU usage data
 */
$cpuUsageStats = $systemMonitoringController->get($timeStart, $timeEnd);

foreach ($cpuUsageStats as $stat) {
    $labels[] = $stat['Timestamp'] * 1000;
    $datasets[0]['data'][] = $stat['Cpu_usage'];
}

/**
 *  Add current CPU usage to the list
 */
$labels[] = time() * 1000;
$datasets[0]['data'][] = \Controllers\System\Monitoring\Cpu::getUsage();

/**
 *  Prepare chart data
 */
$options['title']['text'] = 'CPU usage (%)';
$options['init-zoom'] = 60;
$datasets[0]['color'] = '#F32F63';
$datasets[0]['name'] = 'CPU usage';

unset($systemMonitoringController, $cpuUsageStats, $stat);
