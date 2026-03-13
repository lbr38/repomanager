<?php
$repoController = new \Controllers\Repo\Repo();
$debRepoStatController = new \Controllers\Repo\Statistic\Deb();
$rpmRepoStatController = new \Controllers\Repo\Statistic\Rpm();

if (empty(__ACTUAL_URI__[3])) {
    throw new Exception('Error: missing repository ID.');
}

if (!is_numeric(__ACTUAL_URI__[3])) {
    throw new Exception('Error: invalid repository ID specified.');
}

// Get repository info
$repoController->getAllById(__ACTUAL_URI__[3]);

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
