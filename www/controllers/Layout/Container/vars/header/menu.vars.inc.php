<?php
$myTask = new \Controllers\Task\Task();

/**
 *  Get running tasks
 */
$tasksRunning = $myTask->listRunning();

/**
 *  Count running tasks
 */
$totalRunningTasks = count($tasksRunning);

/**
 *  Get current CPU load
 */
$currentLoad = sys_getloadavg();
$currentLoad = substr($currentLoad[0], 0, 4);
$currentLoadColor = 'green';

if ($currentLoad >= 2) {
    $currentLoadColor = 'yellow';
}
if ($currentLoad >= 3) {
    $currentLoadColor = 'red';
}
