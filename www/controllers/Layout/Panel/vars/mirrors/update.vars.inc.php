<?php
use \Controllers\User\Permission\Mirror as MirrorPermission;

if (!MirrorPermission::allowedAction('update')) {
    throw new Exception('You are not allowed to access this panel');
}

$mirrorSnapshotController = new \Controllers\Mirror\Snapshot();
$userController = new \Controllers\User\User();

// One form block per mirror of the selected snapshots, several snapshots of the same mirror give a single sync
$mirrors = [];

foreach ($mirrorSnapshotController->getSelection($item['snapshots'] ?? '') as $selected) {
    $mirrors[$selected['mirror']['Id']] = $selected['mirror'];
}

// Needed by the schedule form
$usersEmail = $userController->getEmails();

unset($mirrorSnapshotController, $userController, $selected);
