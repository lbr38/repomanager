<?php
use \Controllers\Utils\Generate\Html\Color;

$hostController = new \Controllers\Host();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts OS
$oss = $hostController->listCountOS();

foreach ($oss as $os) {
    if (empty($os['Os'])) {
        $labels[] = 'Unknown';
    } else {
        $labels[] = ucfirst($os['Os']) . ' ' . $os['Os_version'];
    }

    $datasets[0]['data'][] = $os['Os_count'];
    $datasets[0]['colors'][] = Color::random();
}

unset($hostController, $oss, $os);
