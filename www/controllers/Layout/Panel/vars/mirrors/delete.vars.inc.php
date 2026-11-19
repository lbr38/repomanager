<?php
use \Controllers\Mirror\Mirror;
use \Controllers\User\Permission\Mirror as MirrorPermission;

if (!MirrorPermission::allowedAction('delete')) {
    throw new Exception('You are not allowed to access this panel');
}

$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$mirrorEnvController = new \Controllers\Mirror\Environment();
$userController = new \Controllers\User\User();
$protectedEnvs = (new \Controllers\Environment())->getProtected();

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

    // A snapshot used by a protected environment cannot be deleted, other environments are removed with the snapshot
    $envs = $mirrorEnvController->getBySnapId((int) $snapshot['Id']);

    $snapshots[] = [
        'mirror'        => $mirror,
        'snapshot'      => $snapshot,
        'label'         => Mirror::getSnapshotLabel(Mirror::getSnapshotDirName($snapshot['Date'], $snapshot['Time'])),
        'envs'          => $envs,
        'protectedEnvs' => array_values(array_intersect($envs, $protectedEnvs)),
        'lastOfMirror'  => empty(array_diff($activeSnapIds, $selectedByMirror[$mirror['Id']]))
    ];
}

// If a selected snapshot of a mirror is used by a protected environment, the deletion is refused and the mirror is kept:
// do not warn that the mirror will be deleted
$protectedMirrorIds = [];

foreach ($snapshots as $selected) {
    if (!empty($selected['protectedEnvs'])) {
        $protectedMirrorIds[] = $selected['mirror']['Id'];
    }
}

foreach ($snapshots as $i => $selected) {
    if (in_array($selected['mirror']['Id'], $protectedMirrorIds)) {
        $snapshots[$i]['lastOfMirror'] = false;
    }
}

// Needed by the schedule form
$usersEmail = $userController->getEmails();

unset($mirrorSnapshotController, $mirrorEnvController, $userController, $selection, $selectedByMirror, $selected, $mirror, $snapshot, $activeSnapIds, $envs, $protectedEnvs, $protectedMirrorIds, $i);
