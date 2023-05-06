<?php
$mygroup = new \Controllers\Group('repo');

/**
 *  Get repos list by group
 */
$repoGroupsList = $mygroup->listAllName();

unset($myrepo, $mysource, $mygroup);
