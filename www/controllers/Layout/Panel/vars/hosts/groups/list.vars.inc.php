<?php
if (!IS_ADMIN) {
    throw new Exception('You are not allowed to access this panel');
}

$myhost = new \Controllers\Host();
$mygroup = new \Controllers\Group\Host();

/**
 *  Get hosts groups list
 */
$hostGroupsList = $mygroup->listAll();

unset($myhost);
