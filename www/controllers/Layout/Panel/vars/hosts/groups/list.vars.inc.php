<?php
use Controllers\User\Permission\Host as HostPermission;

// If the user does not have permission to edit host groups, prevent access to this panel
if (!HostPermission::allowedAction('edit-groups')) {
    throw new Exception('You are not allowed to access this panel');
}

$myhost = new \Controllers\Host\Host();
$mygroup = new \Controllers\Group\Host();

/**
 *  Get hosts groups list
 */
$hostGroupsList = $mygroup->listAll();

unset($myhost);
