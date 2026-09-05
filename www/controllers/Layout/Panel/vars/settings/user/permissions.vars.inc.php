<?php
$permissionController = new \Controllers\User\Permission\Permission();
$groupController = new \Controllers\Group\Repo();
$repoListingController = new \Controllers\Repo\Listing();

if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel.');
}

if (!isset($item['Id'])) {
    throw new Exception('User Id not set.');
}

$userId = $item['Id'];

// Get user permissions
$permissions = $permissionController->get($userId);

// Get repositories groups
$groupsList = $groupController->listAll(true);

// Get repositories, deduplicated by repository Id as the listing returns one row per snapshot and environment
$reposList = [];

foreach ($repoListingController->list() as $repo) {
    if (isset($reposList[$repo['repoId']])) {
        continue;
    }

    $label = $repo['Name'];

    if ($repo['Package_type'] == 'deb') {
        $label .= ' ❯ ' . $repo['Dist'] . ' ❯ ' . $repo['Section'];
    }

    if ($repo['Package_type'] == 'rpm' and !empty($repo['Releasever'])) {
        $label .= ' ❯ ' . $repo['Releasever'];
    }

    $reposList[$repo['repoId']] = $label;
}
