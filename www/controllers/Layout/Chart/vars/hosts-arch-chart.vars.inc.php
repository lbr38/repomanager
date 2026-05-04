<?php
use \Controllers\Utils\Generate\Html\Color;

$hostListingController = new \Controllers\Host\Listing();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts arch
$archs = $hostListingController->getArch();

foreach ($archs as $arch) {
    if (empty($arch['Arch'])) {
        $labels[] = 'Unknown';
    } else {
        $labels[] = $arch['Arch'];
    }

    $datasets[0]['data'][] = $arch['Count'];
    $datasets[0]['colors'][] = Color::random();
}

unset($hostListingController, $archs, $arch);
