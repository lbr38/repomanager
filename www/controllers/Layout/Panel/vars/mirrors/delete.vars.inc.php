<?php
use \Controllers\Mirror\Mirror;
use \Controllers\User\Permission\Repo as RepoPermission;

// Mirrors use the same permissions as repositories
if (!RepoPermission::allowedAction('delete')) {
    throw new Exception('You are not allowed to access this panel');
}

$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$mirrorEnvController = new \Controllers\Mirror\Environment();
$userController = new \Controllers\User\User();

// One form block per selected snapshot
$snapshots = [];
$selectedByMirror = [];
$selection = $mirrorSnapshotController->getSelection($item['snapshots'] ?? '');

foreach ($selection as $selected) {
    $selectedByMirror[$selected['mirror']['Id']][] = $selected['snapshot']['Id'];
}

foreach ($selection as $selected) {
    $mirror = $selected['mirror'];
    $snapshot = $selected['snapshot'];

    // The mirror is deleted with its last snapshot: warn when all its snapshots are selected
    $activeSnapIds = array_column($mirrorSnapshotController->getByMirrorId((int) $mirror['Id']), 'Id');

    $snapshots[] = [
        'mirror'       => $mirror,
        'snapshot'     => $snapshot,
        'label'        => Mirror::getSnapshotLabel(Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time'])),
        'envs'         => $mirrorEnvController->getBySnapId((int) $snapshot['Id']),
        'lastOfMirror' => empty(array_diff($activeSnapIds, $selectedByMirror[$mirror['Id']]))
    ];
}

// Needed by the schedule form
$usersEmail = $userController->getEmails();

unset($mirrorSnapshotController, $mirrorEnvController, $userController, $selection, $selectedByMirror, $selected, $mirror, $snapshot, $activeSnapIds);
