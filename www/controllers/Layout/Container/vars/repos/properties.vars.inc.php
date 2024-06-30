<?php
$myrepo = new \Controllers\Repo\Repo();
$mytask = new \Controllers\Task\Task();

/**
 *  Get total repos count
 */
$totalRepos = $myrepo->count('active');

/**
 *  Get used space
 */
$diskTotalSpace = disk_total_space(REPOS_DIR);
$diskFreeSpace = disk_free_space(REPOS_DIR);
$diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
$diskTotalSpace = $diskTotalSpace / 1073741824;
$diskUsedSpace = $diskUsedSpace / 1073741824;

/**
 *  Format data to get a percent result without comma
 */
$diskFreeSpace = round(100 - (($diskUsedSpace / $diskTotalSpace) * 100));
$diskFreeSpacePercent = $diskFreeSpace;
$diskUsedSpace = round(100 - ($diskFreeSpace));
$diskUsedSpacePercent = round(100 - ($diskFreeSpace));

/**
 *  If scheduled tasks are enabled the get last and next task results
 */
$lastScheduledTask = $mytask->getLastScheduledTask();
$nextScheduledTasks = $mytask->getNextScheduledTask();

if (!empty($nextScheduledTasks)) {
    $nextScheduledTasksLeft = array();

    foreach ($nextScheduledTasks as $task) {
        $nextScheduledTasksLeft[] = $mytask->getDayTimeLeft($task['Id']);
    }

    $nextScheduledTasks = $nextScheduledTasksLeft;

    /**
     *  Sort tasks by date and time
     */
    array_multisort(array_column($nextScheduledTasks, 'date'), SORT_ASC, array_column($nextScheduledTasks, 'time'), SORT_ASC, $nextScheduledTasks);

    /*
     *  Remove duplicates (when multiple tasks are scheduled for the same date and time)
     */
    $nextScheduledTasks = array_unique($nextScheduledTasks, SORT_REGULAR);
}

unset($myrepo, $mytask, $diskTotalSpace, $nextScheduledTasksLeft);
