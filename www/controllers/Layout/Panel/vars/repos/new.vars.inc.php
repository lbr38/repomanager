<?php
/**
 *  If the user is not an administrator or does not have permission to create repositories, prevent access to this panel.
 */
if (!IS_ADMIN and !in_array('create', USER_PERMISSIONS['repositories']['allowed-actions']['repos'])) {
    throw new Exception('You are not allowed to access this panel');
}

$myrepo = new \Controllers\Repo\Repo();
$mygroup = new \Controllers\Group('repo');
$mysource = new \Controllers\Repo\Source\Source();
$userController = new \Controllers\User\User();

/**
 *  New repo form variables
 */
$newRepoRpmSourcesList = $mysource->listAll('rpm');
$newRepoDebSourcesList = $mysource->listAll('deb');
$newRepoFormGroupList = $mygroup->listAll();

$usersEmail = $userController->getEmails();

unset($userController);
