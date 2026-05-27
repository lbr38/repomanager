<?php
$userPreferenceController = new \Controllers\User\Preference\Preference();
$repoSnapshotController = new \Controllers\Repo\Snapshot\Snapshot();
$repoListingController = new \Controllers\Repo\Listing();
$groupController = new \Controllers\Group\Repo();
$envController = new \Controllers\Environment();
$repoController = new \Controllers\Repo\Repo();

$groups = [];
$repos = [];
$snapshots = [];

// Get user preferences
$userPreferences = $userPreferenceController->get($_SESSION['id']);

// Get protected environments
$protectedEnvs = $envController->getProtected();

// Retrieve all group names
$groupsList = $groupController->listAll(true);

// Define if the user can view all repositories or only specific groups and repositories
$viewAll = IS_ADMIN || in_array('all', USER_PERMISSIONS['repositories']['view']);
$allowedGroups = $viewAll ? [] : USER_PERMISSIONS['repositories']['view']['groups'];
$allowedRepos = $viewAll ? [] : USER_PERMISSIONS['repositories']['view']['repos'];

// Loop through groups to get repos for each group and add them to the $groups array
foreach ($groupsList as $group) {
    // Group is allowed if the user can view all repositories or if the group is in the allowed groups list
    $groupAllowed = $viewAll || in_array($group['Id'], $allowedGroups);
    $groupRepos = [];

    foreach ($repoListingController->listByGroup($group['Name']) as $repo) {
        // A repository granted individually makes its group visible, without revealing the other repositories of that group
        if (!$groupAllowed and !in_array($repo['repoId'], $allowedRepos)) {
            continue;
        }

        // Add the whole repo to the repos array
        $repos[] = $repo;

        // Add the repoId to the group if not already in the group repos (to avoid duplicate repos in the same group)
        if (!in_array($repo['repoId'], $groupRepos)) {
            $groupRepos[] = $repo['repoId'];
        }
    }

    // If the group is allowed or if it has at least one repository that the user can view, add it to the visible groups array
    if ($groupAllowed || !empty($groupRepos)) {
        // Do not display the 'Default' group if it is empty, unless it is the only group
        if ($group['Name'] == 'Default' and count($groupRepos) == 0 and count($groupsList) > 1) {
            continue;
        }

        // Add the group to the visible groups array with its name, repositories, and count of repositories
        $groups[$group['Id']] = [
            'name' => $group['Name'],
            'repos' => $groupRepos,
            'count' => count($groupRepos)
        ];
    }
}

unset($userPreferenceController, $groupController, $repoController, $envController);
