<?php
use \Controllers\Utils\Generate\Html\Color;

$hostController = new \Controllers\Host();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts arch
$archs = $hostController->listCountArch();

foreach ($archs as $arch) {
    if (empty($arch['Arch'])) {
        $labels[] = 'Unknown';
    } else {
        $labels[] = $arch['Arch'];
    }

    $datasets[0]['data'][] = $arch['Arch_count'];
    $datasets[0]['colors'][] = Color::random();
}

unset($hostController, $archs, $arch);
