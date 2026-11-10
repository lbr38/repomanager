<?php
use \Controllers\User\Permission\Repo as RepoPermission;
use \Controllers\User\Permission\Mirror as MirrorPermission;

if (!MirrorPermission::allowedAction('edit')) {
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
