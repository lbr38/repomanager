<?php
use \Controllers\Mirror\Mirror;
use \Controllers\User\Permission\Repo as RepoPermission;

// Mirrors use the same permissions as repositories
if (!RepoPermission::allowedAction('env')) {
    throw new Exception('You are not allowed to access this panel');
}

$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$mirrorEnvController = new \Controllers\Mirror\Environment();
$envController = new \Controllers\Environment();
$userController = new \Controllers\User\User();

// One form block per selected snapshot
$snapshots = [];
$mirrorIds = [];

foreach ($mirrorSnapshotController->getSelection($item['snapshots'] ?? '') as $selected) {
    $mirror = $selected['mirror'];
    $snapshot = $selected['snapshot'];

    // An environment can only point to one snapshot per mirror
    if (in_array($mirror['Id'], $mirrorIds)) {
        throw new Exception('Several snapshots of mirror ' . htmlspecialchars(Mirror::getLabel($mirror['Name'], $mirror['Releasever'])) . ' are selected, an environment can only point to one snapshot per mirror');
    }

    $mirrorIds[] = $mirror['Id'];

    $snapshots[] = [
        'mirror'      => $mirror,
        'snapshot'    => $snapshot,
        'label'       => Mirror::getSnapshotLabel(Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time'])),
        'currentEnvs' => $mirrorEnvController->getBySnapId((int) $snapshot['Id'])
    ];
}

$envs = $envController->getAllByName();
$protectedEnvs = $envController->getProtected();

// Needed by the schedule form
$usersEmail = $userController->getEmails();

unset($mirrorSnapshotController, $mirrorEnvController, $envController, $userController, $mirrorIds, $selected);
