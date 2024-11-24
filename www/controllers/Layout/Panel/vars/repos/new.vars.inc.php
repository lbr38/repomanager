<?php
if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel');
}

$myrepo = new \Controllers\Repo\Repo();
$mygroup = new \Controllers\Group('repo');
$mysource = new \Controllers\Repo\Source\Source();
$mylogin = new \Controllers\Login();

/**
 *  New repo form variables
 */
$newRepoRpmSourcesList = $mysource->listAll('rpm');
$newRepoDebSourcesList = $mysource->listAll('deb');
$newRepoFormGroupList = $mygroup->listAll();

$usersEmail = $mylogin->getEmails();

unset($mylogin);
