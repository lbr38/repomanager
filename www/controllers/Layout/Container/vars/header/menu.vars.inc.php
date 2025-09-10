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
