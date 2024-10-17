<?php
if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel');
}

$myrepo = new \Controllers\Repo\Repo();
$mygroup = new \Controllers\Group('repo');

/**
 *  Get repos groups list
 */
$repoGroupsList = $mygroup->listAll();
