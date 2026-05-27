<?php
$myTask = new \Controllers\Task\Task();
$taskListingController = new \Controllers\Task\Listing();

/**
 *  Get running tasks
 */
$tasksRunning = $taskListingController->getRunning();

/**
 *  Count running tasks
 */
$totalRunningTasks = count($tasksRunning);
