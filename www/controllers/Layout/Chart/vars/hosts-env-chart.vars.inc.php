<?php
use \Controllers\Environment;

$hostListingController = new \Controllers\Host\Listing();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts environments
$envs = $hostListingController->getEnvironment();

foreach ($envs as $env) {
    if (empty($env['Env'])) {
        $name = 'Unknown';
    } else {
        $name = $env['Env'];
    }

    $labels[] = $name;
    $datasets[0]['data'][] = $env['Count'];
    $datasets[0]['colors'][] = Environment::getEnvColor($name);
}

unset($hostListingController, $envs, $env, $name);
