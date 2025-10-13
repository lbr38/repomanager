<?php
$repoSnapshotController = new \Controllers\Repo\Snapshot();
$mygroup = new \Controllers\Group\Repo();
$myrepoListing = new \Controllers\Repo\Listing();

/**
 *  Retrieve all group names
 */
$groupsList = $mygroup->listAll(true);
