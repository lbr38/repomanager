<?php
$myrepoListing = new \Controllers\Repo\Listing();
$mygroup = new \Controllers\Group('repo');
$mylogin = new \Controllers\Login();

/**
 *  Get list of repos with at least DEFAULT_ENV
 */
$reposList = $myrepoListing->listForPlan();

/**
 *  Get repos groups list
 */
$groupsList = $mygroup->listAll();

/**
 *  Getting users email
 */
$usersEmail = $mylogin->getEmails();

unset($myrepoListing, $mygroup, $mylogin);
