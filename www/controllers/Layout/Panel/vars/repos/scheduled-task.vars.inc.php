<?php
use \Controllers\User\User;
use \Controllers\Task\Target;
use \Controllers\Group\Repo as RepoGroup;
use \Controllers\Repo\Listing as RepoListing;
use \Controllers\User\Permission\Repo as RepoPermission;

$targetController = new Target();
$groupController = new RepoGroup();
$repoListingController = new RepoListing();
$userController = new User();

// Only keep the actions the user is allowed to execute
$allowedActions = [];

foreach ($targetController->getValidActions() as $action) {
    if (RepoPermission::allowedAction($action)) {
        $allowedActions[] = $action;
    }
}

if (empty($allowedActions)) {
    throw new Exception('You are not allowed to access this panel');
}

// Human readable title for each action
$actionsTitles = [
    'update' => 'Update repositories',
    'env' => 'Point an environment',
    'rebuild' => 'Rebuild repositories metadata'
];

// Retrieve the groups, the tags and the recipients to build the target selectors
$groups = $groupController->listAll(true);
$tags = $repoListingController->listTags();
$usersEmail = $userController->getEmails();

unset($targetController, $groupController, $repoListingController, $userController);
