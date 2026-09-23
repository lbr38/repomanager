<?php
$taskListingController = new \Controllers\Task\Listing();

// Get all tasks and count them
$totalCount = count($taskListingController->get());

// Get running tasks
$runningCount = count($taskListingController->getRunning());

// Get queued/pending tasks
$queuedCount = count($taskListingController->getQueued(false));

// Get scheduled tasks
$scheduledCount = count($taskListingController->getScheduled());

unset($taskListingController);
