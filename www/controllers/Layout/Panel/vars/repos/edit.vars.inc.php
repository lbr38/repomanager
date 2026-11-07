<?php
use \Controllers\User\Permission\Repo as RepoPermission;

/**
 *  If the user is not an administrator or does not have permission to edit repositories, prevent access to this panel.
 */
if (!RepoPermission::allowedAction('edit')) {
    throw new Exception('You are not allowed to access this panel');
}

$repoController = (new \Controllers\Repo\Edit\Form())->getRepo($item['repo-id'] ?? null);

/**
 *  Get source repositories
 */
$sourceController = new \Controllers\Repo\Source\Source();
$newRepoRpmSourcesList = $sourceController->listAll('rpm');
$newRepoDebSourcesList = $sourceController->listAll('deb');

unset($sourceController);
