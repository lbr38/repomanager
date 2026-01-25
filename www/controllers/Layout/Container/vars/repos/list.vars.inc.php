<?php
$repoSnapshotController = new \Controllers\Repo\Snapshot();
$groupController = new \Controllers\Group\Repo();
$repoController = new \Controllers\Repo\Repo();
$myrepoListing = new \Controllers\Repo\Listing();
$taskController = new \Controllers\Task\Task();

// Retrieve all group names
$groupsList = $groupController->listAll(true);

// Get total repos count
$totalRepos = $repoController->count();

// Get used and free disk space in bytes, and also in percent
$diskTotalSpace = disk_total_space(REPOS_DIR);
$diskFreeSpace  = disk_free_space(REPOS_DIR);
$diskUsedSpace  = $diskTotalSpace - $diskFreeSpace;
$diskFreeSpaceHuman = \Controllers\Utils\Convert::sizeToHuman($diskFreeSpace);
$diskUsedSpaceHuman = \Controllers\Utils\Convert::sizeToHuman($diskUsedSpace);
$diskUsedSpacePercent = round(($diskUsedSpace / $diskTotalSpace) * 100);
$diskFreeSpacePercent = round(($diskFreeSpace / $diskTotalSpace) * 100);

// Get last and next scheduled tasks
$lastScheduledTask = $taskController->getLastScheduledTask();
$nextScheduledTasks = $taskController->getNextScheduledTask();

if (!empty($nextScheduledTasks)) {
    $data = [];

    foreach ($nextScheduledTasks as $task) {
        $data[] = $taskController->getDayTimeLeft($task['Id']);
    }

    $nextScheduledTasks = $data;

    // Sort tasks by date and time
    array_multisort(array_column($nextScheduledTasks, 'date'), SORT_ASC, array_column($nextScheduledTasks, 'time'), SORT_ASC, $nextScheduledTasks);

    // Remove duplicates (when multiple tasks are scheduled for the same date and time)
    $nextScheduledTasks = array_unique($nextScheduledTasks, SORT_REGULAR);
}

unset($groupController, $repoController, $taskController, $diskTotalSpace, $diskFreeSpace, $diskUsedSpace, $data);
