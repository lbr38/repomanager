<?php
$permissionController = new \Controllers\User\Permission();
$groupController = new \Controllers\Group('repo');

if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel.');
}

if (!isset($item['Id'])) {
    throw new Exception('User Id not set.');
}

$userId = $item['Id'];

/**
 *  Get user permissions
 */
$permissions = $permissionController->get($userId);

/**
 *  Get repositories groups
 */
$groupsList = $groupController->listAll(true);
