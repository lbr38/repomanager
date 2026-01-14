<?php
use \Controllers\Environment;

$hostController = new \Controllers\Host();
$datasets = [];
$labels = [];
$options = [];

// Getting a list of all hosts environments
$envs = $hostController->listCountEnv();

foreach ($envs as $env) {
    if (empty($env['Env'])) {
        $name = 'Unknown';
    } else {
        $name = $env['Env'];
    }

    $labels[] = $name;
    $datasets[0]['data'][] = $env['Env_count'];
    $datasets[0]['colors'][] = Environment::getEnvColor($name);
}

unset($hostController, $envs, $env, $name);
