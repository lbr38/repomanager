<?php
$repoSnapshotController = new \Controllers\Repo\Snapshot\Snapshot();
$groupController = new \Controllers\Group\Repo();
$repoController = new \Controllers\Repo\Repo();
$repoListingController = new \Controllers\Repo\Listing();

$groups = [];
$repos = [];
$snapshots = [];

// TODO 6.0.0: add editable settings in the repos list
$repoListSettings = [
    // expand the repo if it's the only one in the group, otherwise show it in a grid if there are multiple repos in the group
    'expand' => true,
    // Set all repos in one line (override previous setting)
    'one-line' => true
];

// Retrieve all group names
$groupsList = $groupController->listAll(true);

// Loop through groups to get repos for each group and add them to the $groups array
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

    foreach ($repoListingController->listByGroup($group['Name']) as $repo) {
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

        // TODO 6.0.0: If the repository is in the user permissions, then the group become showable for the user

        $previousId = $repo['repoId'];
    }
}

unset($groupController, $repoController);
