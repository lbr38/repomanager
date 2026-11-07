<?php
use \Controllers\User\Permission\Repo as RepoPermission;

// Mirrors use the same permissions as repositories
if (!RepoPermission::allowedAction('create')) {
    throw new Exception('You are not allowed to access this panel');
}

$sourceController = new \Controllers\Repo\Source\Source();
$userController = new \Controllers\User\User();

$rpmSourcesList = $sourceController->listAll('rpm');
$debSourcesList = $sourceController->listAll('deb');
$archSourcesList = $sourceController->listAll('arch');

// Existing tags (repositories and mirrors) and groups, as in the new repository form
$tags = (new \Controllers\Mirror\Mirror())->listAllTags();
$groups = (new \Controllers\Group\Repo())->listAll();

// Needed by the schedule form
$usersEmail = $userController->getEmails();

unset($sourceController, $userController);
