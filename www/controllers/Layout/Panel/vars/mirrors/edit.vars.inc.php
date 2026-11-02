<?php
use \Controllers\User\Permission\Repo as RepoPermission;

// Mirrors use the same permissions as repositories
if (!RepoPermission::allowedAction('edit')) {
    throw new Exception('You are not allowed to access this panel');
}

if (empty($item['mirror-id']) or !is_numeric($item['mirror-id'])) {
    throw new Exception('Mirror Id is invalid');
}

$mirrorController = new \Controllers\Mirror\Mirror();

$mirror = $mirrorController->getById((int) $item['mirror-id']);

if (empty($mirror) or !RepoPermission::allowedToViewMirror((int) $mirror['Id'])) {
    throw new Exception('Mirror does not exist');
}

unset($mirrorController);
