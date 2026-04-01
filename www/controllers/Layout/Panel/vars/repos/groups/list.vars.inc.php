<?php
use Controllers\User\Permission\Repo as RepoPermission;

// If the user does not have permission to edit repository groups, prevent access to this panel
if (!RepoPermission::allowedAction('edit-groups')) {
    throw new Exception('You are not allowed to access this panel');
}

$myrepo = new \Controllers\Repo\Repo();
$mygroup = new \Controllers\Group\Repo();

/**
 *  Get repos groups list
 */
$repoGroupsList = $mygroup->listAll();
