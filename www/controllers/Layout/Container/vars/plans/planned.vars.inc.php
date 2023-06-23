<?php
$myrepo = new \Controllers\Repo\Repo();
$myplan = new \Controllers\Planification();
$mygroup = new \Controllers\Group('repo');

/**
 *  Get planifications list
 */
$planQueueList = $myplan->listQueue();
$planRunningList = $myplan->listRunning();
$planDisabledList = $myplan->listDisabled();

$planList = array_merge($planRunningList, $planQueueList, $planDisabledList);
array_multisort(array_column($planList, 'Date'), SORT_ASC, array_column($planList, 'Time'), SORT_ASC, $planList);
