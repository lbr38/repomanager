<?php
$myrepo = new \Controllers\Repo();
$mygroup = new \Controllers\Group('repo');
$mylogin = new \Controllers\Login();

/**
 *  Get list of repos with at least DEFAULT_ENV
 */
$reposList = $myrepo->listForPlan();

/**
 *  Get repos groups list
 */
$groupsList = $mygroup->listAll();

/**
 *  Getting users email
 */
$usersEmail = $mylogin->getEmails();

unset($myrepo, $mygroup, $mylogin);
