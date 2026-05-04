<?php
use \Controllers\Utils\Generate\Html\Color;

$hostListingController = new \Controllers\Host\Listing();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts OS
$oss = $hostListingController->getOs();

foreach ($oss as $os) {
    if (empty($os['Os'])) {
        $labels[] = 'Unknown';
    } else {
        $labels[] = ucfirst($os['Os']) . ' ' . $os['Os_version'];
    }

    $datasets[0]['data'][] = $os['Count'];
    $datasets[0]['colors'][] = Color::random();
}

unset($hostListingController, $oss, $os);
