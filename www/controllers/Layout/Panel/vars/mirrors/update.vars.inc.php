<?php
use \Controllers\User\Permission\Repo as RepoPermission;

// Mirrors use the same permissions as repositories
if (!RepoPermission::allowedAction('update')) {
    throw new Exception('You are not allowed to access this panel');
}

$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$userController = new \Controllers\User\User();
$envController = new \Controllers\Environment();

// One form block per mirror of the selected snapshots, several snapshots of the same mirror give a single sync
$mirrors = [];

foreach ($mirrorSnapshotController->getSelection($item['snapshots'] ?? '') as $selected) {
    $mirrors[$selected['mirror']['Id']] = $selected['mirror'];
}

$envs = $envController->getAllByName();

// Needed by the schedule form
$usersEmail = $userController->getEmails();

unset($mirrorSnapshotController, $userController, $envController, $selected);
