<?php
use \Controllers\Utils\Convert;

$repoController = new \Controllers\Repo\Repo();
$taskController = new \Controllers\Task\Task();

// Get total repos count
$totalRepos = $repoController->count();

// Get used and free disk space in bytes, and also in percent
$diskTotalSpace = disk_total_space(REPOS_DIR);
$diskFreeSpace  = disk_free_space(REPOS_DIR);
$diskUsedSpace  = $diskTotalSpace - $diskFreeSpace;
$diskFreeSpaceHuman = Convert::sizeToHuman($diskFreeSpace);
$diskUsedSpaceHuman = Convert::sizeToHuman($diskUsedSpace);
$diskUsedSpacePercent = round(($diskUsedSpace / $diskTotalSpace) * 100);
$diskFreeSpacePercent = round(($diskFreeSpace / $diskTotalSpace) * 100);

if ($diskUsedSpacePercent <= 50) {
    $diskUsedSpaceClass = 'storage-meter-accent-green';
} elseif ($diskUsedSpacePercent <= 80) {
    $diskUsedSpaceClass = 'storage-meter-accent-yellow';
} else {
    $diskUsedSpaceClass = 'storage-meter-accent-red';
}

// Mirrors count, and mirrors storage if mirrors are stored on another filesystem than repositories
$totalMirrors = 0;
$mirrorsStorage = [];

// Only count the mirrors the user can view
foreach ((new \Controllers\Mirror\Mirror())->get() as $mirror) {
    if (\Controllers\User\Permission\Repo::allowedToViewMirror($mirror['Id'])) {
        $totalMirrors++;
    }
}

unset($mirror);

if ($totalMirrors > 0 and is_dir(MIRRORS_DIR) and stat(MIRRORS_DIR)['dev'] != stat(REPOS_DIR)['dev']) {
    $mirrorsTotalSpace = disk_total_space(MIRRORS_DIR);
    $mirrorsFreeSpace = disk_free_space(MIRRORS_DIR);
    $mirrorsStorage['percent'] = round((($mirrorsTotalSpace - $mirrorsFreeSpace) / $mirrorsTotalSpace) * 100);
    $mirrorsStorage['used'] = Convert::sizeToHuman($mirrorsTotalSpace - $mirrorsFreeSpace);
    $mirrorsStorage['free'] = Convert::sizeToHuman($mirrorsFreeSpace);
    $mirrorsStorage['class'] = $mirrorsStorage['percent'] <= 50 ? 'storage-meter-accent-green' : ($mirrorsStorage['percent'] <= 80 ? 'storage-meter-accent-yellow' : 'storage-meter-accent-red');

    unset($mirrorsTotalSpace, $mirrorsFreeSpace);
}

// Get last and next scheduled tasks
$lastScheduledTask = $taskController->getLastScheduledTask();
$nextScheduledTasks = $taskController->getNextScheduledTask();
$lastScheduledTaskSummary = [];

// If the last scheduled task dispatched sub-tasks, its real status is a summary of their statuses
if (!empty($lastScheduledTask['Id'])) {
    $lastScheduledTaskSummary = (new \Controllers\Task\Listing())->getSubTasksSummary($lastScheduledTask['Id']);
}

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

unset($repoController, $taskController, $diskTotalSpace, $diskFreeSpace, $diskUsedSpace, $data);
