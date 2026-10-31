<?php
use \Controllers\User\Permission\Repo as RepoPermission;

// Mirrors use the same permissions as repositories
if (!RepoPermission::allowedAction('create')) {
    throw new Exception('You are not allowed to access this panel');
}

$sourceController = new \Controllers\Repo\Source\Source();
$userController = new \Controllers\User\User();
$envController = new \Controllers\Environment();

$rpmSourcesList = $sourceController->listAll('rpm');
$debSourcesList = $sourceController->listAll('deb');
$envs = $envController->getAllByName();

// Existing tags (repositories and mirrors) and groups, as in the new repository form
$tags = (new \Controllers\Repo\Listing())->listTags();

foreach ((new \Controllers\Mirror\Mirror())->get() as $mirror) {
    $tags = array_merge($tags, array_filter(explode(',', $mirror['Tags'] ?? '')));
}

$tags = array_unique($tags);
sort($tags);
$groups = (new \Controllers\Group\Repo())->listAll();

// Needed by the schedule form
$usersEmail = $userController->getEmails();

unset($sourceController, $userController, $envController);
