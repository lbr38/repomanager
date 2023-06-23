<?php
$myrepo = new \Controllers\Repo\Repo();
$myplan = new \Controllers\Planification();
$mygroup = new \Controllers\Group('repo');

$plansDone = $myplan->listDone();

unset($myplan);
