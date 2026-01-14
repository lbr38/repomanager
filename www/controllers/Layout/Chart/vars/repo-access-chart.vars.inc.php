<?php
$repoController = new \Controllers\Repo\Repo();
$statsController = new \Controllers\Stat();
$datasets = [];
$labels = [];
$options = [];

// Check that env Id is specified
if (empty(__ACTUAL_URI__[2])) {
    throw new Exception('no snapshot env ID specified');
}

// Check that env Id is valid
if (!is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid snapshot env ID');
}

// Retrieve repo infos from DB
$repoController->getAllById('', '', __ACTUAL_URI__[2]);

// First create a list of dates
$dateCounter = date_create(date('Y-m-d'))->modify('-' . $days . ' days')->format('Y-m-d');
$dateEnd = date_create(date('Y-m-d'))->modify('+1 days')->format('Y-m-d');

// Process all dates until the current date (which is also processed)
while ($dateCounter < $dateEnd) {
    $labels[] = $dateCounter;

    if ($repoController->getPackageType() == 'rpm') {
        $datasets[0]['data'][] = $statsController->getDailyAccessCount('rpm', $repoController->getName(), '', '', $repoController->getEnv(), $dateCounter);
    }
    if ($repoController->getPackageType() == 'deb') {
        $datasets[0]['data'][] = $statsController->getDailyAccessCount('deb', $repoController->getName(), $repoController->getDist(), $repoController->getSection(), $repoController->getEnv(), $dateCounter);
    }

    // Increment by 1 day to process the next date
    $dateCounter = date('Y-m-d', strtotime('+1 day', strtotime($dateCounter)));
}

unset($repoController, $statsController, $dateCounter, $dateEnd);
