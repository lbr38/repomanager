<?php
$systemMonitoringController = new \Controllers\System\Monitoring\Monitoring();
$datasets = [];
$labels = [];
$options = [];

/**
 *  Get disk usage data
 *  This will fetch the last 60 minutes of disk usage data
 */
$diskUsageStats = $systemMonitoringController->get(time() - 3600, time());

foreach ($diskUsageStats as $stat) {
    // Convert timestamp to a human-readable format using Datetime
    $labels[] = (new DateTime())->setTimestamp($stat['Timestamp'])->format('H:i:s');
    $datasets[0]['data'][] = $stat['Disk_usage'];
}

/**
 *  Add current disk usage to the list
 */
$labels[] = date('H:i:s');
$datasets[0]['data'][] = \Controllers\System\Monitoring\Disk::getUsage(REPOS_DIR);

/**
 *  Prepare chart data
 */
$options['title']['text'] = 'Disk usage (%)';
$options['legend']['display']['position'] = 'bottom';

$datasets[0]['backgroundColor'] = 'rgba(243, 47, 99, 0.20)';
$datasets[0]['borderColor'] = '#F32F63';
$datasets[0]['label'] = 'Disk usage';

unset($systemMonitoringController, $diskUsageStats, $stat);
