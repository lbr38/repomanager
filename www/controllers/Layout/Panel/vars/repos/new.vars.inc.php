<?php
use \Controllers\User\User;
use \Controllers\Group\Repo as RepoGroup;
use \Controllers\Repo\Listing as RepoListing;
use \Controllers\User\Permission\Repo as RepoPermission;

// Check if the user has the permission to create a new repository
if (!RepoPermission::allowedAction('create')) {
    throw new Exception('You are not allowed to access this panel');
}

$groupController = new RepoGroup();
$repoListingController = new RepoListing();
$userController = new User();
$sourceController = new \Controllers\Repo\Source\Source();

// New repo form variables
$rpmSourcesList = $sourceController->listAll('rpm');
$debSourcesList = $sourceController->listAll('deb');

// Retrieve the groups, the tags and the recipients to build the target selectors
$groups = $groupController->listAll();
$tags = $repoListingController->listTags();
$usersEmail = $userController->getEmails();

unset($targetController, $groupController, $repoListingController, $userController, $sourceController);
