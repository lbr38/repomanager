<?php

$myplan = new \Controllers\Planification();
$myop = new \Controllers\Operation();

/**
 *  Get a list of all running planifications
 */
$plansRunning = $myplan->listRunning();

/**
 *  Get a list of all done planifications
 */
$plansDone = $myplan->listDone();

/**
 *  If the previous requests both returned a result, then we merge these results into $totalRunning
 */
if (!empty($plansRunning) and !empty($opsRunning)) {
    $totalRunning = array_merge($plansRunning, $opsRunning);
    array_multisort(array_column($totalRunning, 'Date'), SORT_DESC, array_column($totalRunning, 'Time'), SORT_DESC, $totalRunning); // On tri par date pour avoir le + récent en haut
} elseif (!empty($plansRunning)) {
    $totalRunning = $plansRunning;
} elseif (!empty($opsRunning)) {
    $totalRunning = $opsRunning;
}

/**
 *  Get a list of all running operations that have not been launched by a planification (type = manual)
 */
$opsRunning = $myop->listRunning('manual');

/**
 *  Get a list of all done operations that have not been launched by a planification (type = manual)
 */
$opsDone = $myop->listDone('manual');

/**
 *  Get all done operations that have been launched by a regular planification
 */
$opsFromRegularPlanDone = $myop->listDone('plan', 'regular');

/**
 *  If the previous requests returned a result, then we merge these results into $totalRunning
 */
if (!empty($plansRunning) and !empty($opsRunning)) {
    $totalRunning = array_merge($plansRunning, $opsRunning);
    array_multisort(array_column($totalRunning, 'Date'), SORT_DESC, array_column($totalRunning, 'Time'), SORT_DESC, $totalRunning); // On tri par date pour avoir le + récent en haut
} elseif (!empty($plansRunning)) {
    $totalRunning = $plansRunning;
} elseif (!empty($opsRunning)) {
    $totalRunning = $opsRunning;
}

if (!empty($plansDone) and !empty($opsDone)) {
    $totalDone = array_merge($plansDone, $opsDone);
    array_multisort(array_column($totalDone, 'Date'), SORT_DESC, array_column($totalDone, 'Time'), SORT_DESC, $totalDone); // On tri par date pour avoir le + récent en haut
} else if (!empty($plansDone)) {
    $totalDone = $plansDone;
} else if (!empty($opsDone)) {
    $totalDone = $opsDone;
}
