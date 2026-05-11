<?php
$taskController = new \Controllers\Task\Task();

// Get all tasks and count them
$totalCount = count($taskController->get());

// Get running tasks
$runningCount = count($taskController->listRunning());

// Get scheduled tasks
$scheduledCount = count($taskController->listScheduled());

unset($taskController);
