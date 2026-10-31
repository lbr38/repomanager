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

// Mirrors, displayed with repositories in their group ('Default' group if they are not member of any group)
// They are visible with the same rules as repositories: all repositories, their group, or granted individually
$mirrorsByGroup = [];
$allowedMirrors = $viewAll ? [] : (USER_PERMISSIONS['repositories']['view']['mirrors'] ?? []);

$mirrorController = new \Controllers\Mirror\Mirror();
$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$mirrorEnvController = new \Controllers\Mirror\Environment();
$mirrorsGroupsIds = $mirrorController->getGroupsIds();

foreach ($mirrorController->get() as $mirror) {
    $mirror['params'] = $mirrorController->getParams($mirror);
    $mirror['snapshots'] = [];
    $mirror['envs'] = [];

    foreach ($mirrorSnapshotController->getByMirrorId($mirror['Id']) as $snapshot) {
        $snapshot['dirName'] = \Controllers\Mirror\Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time']);
        $snapshot['envs'] = $mirrorEnvController->getBySnapId($snapshot['Id']);
        $mirror['envs'] = array_merge($mirror['envs'], $snapshot['envs']);
        $mirror['snapshots'][] = $snapshot;
    }

    $mirrorsByGroup[$mirrorsGroupsIds[$mirror['Id']] ?? 'Default'][] = $mirror;
}

unset($mirrorController, $mirrorSnapshotController, $mirrorEnvController, $mirrorsGroupsIds, $mirror, $snapshot);

// Loop through groups to get repos for each group and add them to the $groups array
foreach ($groupsList as $group) {
    // Group is allowed if the user can view all repositories or if the group is in the allowed groups list
    $groupAllowed = $viewAll || in_array($group['Id'], $allowedGroups);
    $groupRepos = [];
    $groupMirrors = [];

    foreach ($mirrorsByGroup[$group['Name'] == 'Default' ? 'Default' : $group['Id']] ?? [] as $mirror) {
        // As repositories, a mirror granted individually makes its group visible, without revealing the other members of that group
        if ($groupAllowed or in_array($mirror['Id'], $allowedMirrors)) {
            $groupMirrors[] = $mirror;
        }
    }

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

    // If the group is allowed or if it has at least one repository or mirror that the user can view, add it to the visible groups array
    if ($groupAllowed || !empty($groupRepos) || !empty($groupMirrors)) {
        // Do not display the 'Default' group if it is empty, unless it is the only group
        if ($group['Name'] == 'Default' and count($groupRepos) == 0 and count($groupMirrors) == 0 and count($groupsList) > 1) {
            continue;
        }

        // Add the group to the visible groups array with its name, repositories, mirrors, and count of repositories
        $groups[$group['Id']] = [
            'name' => $group['Name'],
            'repos' => $groupRepos,
            'mirrors' => $groupMirrors,
            'count' => count($groupRepos) + count($groupMirrors)
        ];
    }
}

unset($userPreferenceController, $groupController, $repoController, $envController, $mirrorsByGroup, $groupMirrors, $allowedMirrors, $mirror);
