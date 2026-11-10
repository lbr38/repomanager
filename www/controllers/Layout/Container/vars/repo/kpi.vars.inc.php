<?php
// Mirror page (/repository/mirror/<id>)
$isMirror = (__ACTUAL_URI__[2] ?? '') == 'mirror';

if ($isMirror) {
    $mirror = (new \Controllers\Mirror\Mirror())->getForPage(__ACTUAL_URI__[3] ?? '');
    $snapshots = (new \Controllers\Mirror\Snapshot())->getByMirrorId($mirror['Id']);
    $accessCount = (new \Controllers\Mirror\Statistic())->getAccessCount($mirror['Id'], strtotime('today'), strtotime('tomorrow'));
    return;
}

$repoController = new \Controllers\Repo\Repo();
$debRepoStatController = new \Controllers\Repo\Statistic\Deb();
$rpmRepoStatController = new \Controllers\Repo\Statistic\Rpm();

if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no repository ID specified.');
}

if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid repository ID specified.');
}

// Get repository info
$repoController->getAllById(__ACTUAL_URI__[2]);

// Get all snapshots of the repository
$snapshots = $repoController->getSnapshots($repoController->getRepoId());

// Get today total access count for the repository
if ($repoController->getPackageType() == 'deb') {
    $accessCount = $debRepoStatController->getDailyAccessCount($repoController->getName(), $repoController->getDist(), $repoController->getSection(), [], strtotime('today'), strtotime('tomorrow'));
}

if ($repoController->getPackageType() == 'rpm') {
    $accessCount = $rpmRepoStatController->getDailyAccessCount($repoController->getName(), $repoController->getReleasever(), [], strtotime('today'), strtotime('tomorrow'));
}

unset($debRepoStatController, $rpmRepoStatController);
