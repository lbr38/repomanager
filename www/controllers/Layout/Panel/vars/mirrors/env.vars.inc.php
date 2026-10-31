<?php
use \Controllers\Mirror\Mirror;
use \Controllers\User\Permission\Repo as RepoPermission;

// Mirrors use the same permissions as repositories
if (!RepoPermission::allowedAction('env')) {
    throw new Exception('You are not allowed to access this panel');
}

if (empty($item['snapshots'])) {
    throw new Exception('No snapshot selected');
}

try {
    $selectedSnapshots = json_decode($item['snapshots'], true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    throw new Exception('Could not decode selected snapshots');
}

$mirrorController = new Mirror();
$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$mirrorEnvController = new \Controllers\Mirror\Environment();
$envController = new \Controllers\Environment();
$userController = new \Controllers\User\User();

// One form block per selected snapshot
$snapshots = [];
$mirrorIds = [];

foreach ($selectedSnapshots as $selected) {
    if (empty($selected['mirror-id']) or !is_numeric($selected['mirror-id']) or empty($selected['mirror-snap-id']) or !is_numeric($selected['mirror-snap-id'])) {
        throw new Exception('Mirror or snapshot Id is invalid');
    }

    $mirror = $mirrorController->getById((int) $selected['mirror-id']);
    $snapshot = $mirrorSnapshotController->getById((int) $selected['mirror-snap-id']);

    if (empty($mirror) or empty($snapshot) or $snapshot['Id_mirror'] != $mirror['Id'] or $snapshot['Status'] != 'active' or !RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
        throw new Exception('Mirror or snapshot does not exist');
    }

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

unset($mirrorController, $mirrorSnapshotController, $mirrorEnvController, $envController, $userController, $selectedSnapshots, $mirrorIds);
