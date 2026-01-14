<?php
$datasets = [];
$labels = [];
$options = [];

// Get used and free disk space in bytes, and also in percent
$diskTotalSpace = disk_total_space(REPOS_DIR);
$diskFreeSpace  = disk_free_space(REPOS_DIR);
$diskUsedSpace  = $diskTotalSpace - $diskFreeSpace;
$diskFreeSpaceHuman = \Controllers\Utils\Convert::sizeToHuman($diskFreeSpace);
$diskUsedSpaceHuman = \Controllers\Utils\Convert::sizeToHuman($diskUsedSpace);
$diskUsedSpacePercent = round(($diskUsedSpace / $diskTotalSpace) * 100);
$diskFreeSpacePercent = round(($diskFreeSpace / $diskTotalSpace) * 100);

$labels[] = 'Used: ' . $diskUsedSpaceHuman;
$labels[] = 'Free: ' . $diskFreeSpaceHuman;
$datasets[0]['data'][] = $diskUsedSpacePercent;
$datasets[0]['data'][] = $diskFreeSpacePercent;

if ($diskUsedSpacePercent > 0 && $diskUsedSpacePercent <= 30) {
    $datasets[0]['colors'][] = '#15bf7f';
    $datasets[0]['colors'][] = 'rgba(21, 191, 127, 0.40)';
}
if ($diskUsedSpacePercent > 30 && $diskUsedSpacePercent <= 50) {
    $datasets[0]['colors'][] = '#ffb536';
    $datasets[0]['colors'][] = 'rgba(255, 181, 54, 0.40)';
}
if ($diskUsedSpacePercent > 50 && $diskUsedSpacePercent <= 70) {
    $datasets[0]['colors'][] = '#ff7c49';
    $datasets[0]['colors'][] = 'rgba(255, 124, 73, 0.40)';
}
if ($diskUsedSpacePercent > 70 && $diskUsedSpacePercent <= 100) {
    $datasets[0]['colors'][] = '#F32F63';
    $datasets[0]['colors'][] = 'rgba(243, 47, 99, 0.40)';
}

$options['innerRadius'] = '65%';
$options['outerRadius'] = '99%';
$options['toolbox']['show'] = false;
$options['legend']['show'] = false;
$options['tooltip']['show'] = false;
$options['emphasis']['disabled'] = true;

unset($diskTotalSpace, $diskFreeSpace, $diskUsedSpace);
