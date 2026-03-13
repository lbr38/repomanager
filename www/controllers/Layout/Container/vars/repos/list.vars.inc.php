<?php
use \Controllers\Utils\Convert;

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
$diskFreeSpaceHuman = Convert::sizeToHuman($diskFreeSpace);
$diskUsedSpaceHuman = Convert::sizeToHuman($diskUsedSpace);
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


$groups = [];
$repos = [];
$snapshots = [];

foreach ($groupsList as $group) {
    $show = true;
    $previousId = null;

    /**
     *  Permissions
     *  If the user is not an admin, check if the group is in the user permissions
     */
    if (!IS_ADMIN) {
        // If 'all' is not in the user permissions, then it means the user has specific permissions and cannot view all groups
        if (!in_array('all', USER_PERMISSIONS['repositories']['view'])) {
            // Check if the current group Id is in the user permissions, if not then skip to the next group
            if (!in_array($group['Id'], USER_PERMISSIONS['repositories']['view']['groups'])) {
                $show = false;
            }
        }
    }

    if (!isset($groups[$group['Id']])) {
        $groups[$group['Id']] = [
            'name' => $group['Name'],
            'repos' => [],
            'count' => 0,
            'show' => $show
        ];
    }

    foreach ($myrepoListing->listByGroup($group['Name']) as $repo) {
        // Add the whole repo to the repos array
        $repos[] = $repo;

        // Add the repoId to the group if not already in the group repos (to avoid duplicate repos in the same group)
        if (!in_array($repo['repoId'], $groups[$group['Id']]['repos'])) {
            $groups[$group['Id']]['repos'][] = $repo['repoId'];
        }

        // Add 1 to the group count only if the repoId is different from the previous one
        if ($previousId != $repo['repoId']) {
            $groups[$group['Id']]['count']++;
        }

        // TODO: If the repository is in the user permissions, then the group become showable for the user

        $previousId = $repo['repoId'];
    }



}

unset($groupController, $repoController, $taskController, $diskTotalSpace, $diskFreeSpace, $diskUsedSpace, $data);
