<?php
use \Controllers\Task\Task;
use \Controllers\Repo\Source\Source;

$sourceController = new Source();
$taskController = new Task();
$debParamsTemplate = [
    'action' => 'create',
    'package-type' => 'deb',
    'repo-type' => 'mirror',
    'source' => '',
    'alias' => '',
    'dist' => [],
    'section' => [],
    'arch' => [
        'amd64'
    ],
    'env' => [
        'preprod'
    ],
    'description' => '',
    'tags' => [],
    'gpg-check' => 'true',
    'gpg-sign' => 'false',
    'advanced-params' => [
        'packages' => [
            'keep-latest' => '1',
            'include' => ['aaaaaaa.*'],
            'exclude' => [],
        ],
        'metadata-custom-fields' => [
            'origin' => '',
            'label' => '',
            'description' => '',
        ],
    ],

    'schedule' => [
        'scheduled' => 'true',
        'schedule-type' => 'unique',
        'schedule-date' => '',
        'schedule-time' => '',
        'schedule-notify-error' => 'true',
        'schedule-notify-success' => 'false',
        'schedule-reminder' => [],
        'schedule-recipient' => [
            'ci@repomanager.net'
        ],
    ],
];
$rpmParamsTemplate = [
    'action' => 'create',
    'package-type' => 'rpm',
    'repo-type' => 'mirror',
    'source' => '',
    'alias' => '',
    'releasever' => [],
    'arch' => [
        'x86_64',
        'noarch',
        'aarch64'
    ],
    'env' => [
        'preprod'
    ],
    'description' => '',
    'tags' => [],
    'gpg-check' => 'true',
    'gpg-sign' => 'false',
    'advanced-params' => [
        'packages' => [
            'keep-latest' => '1',
            'include' => ['aaaaaaa.*'],
            'exclude' => [],
        ],
        'metadata-custom-fields' => [
            'origin' => '',
            'label' => '',
            'description' => '',
        ],
    ],

    'schedule' => [
        'scheduled' => 'true',
        'schedule-type' => 'unique',
        'schedule-date' => '',
        'schedule-time' => '',
        'schedule-notify-error' => 'true',
        'schedule-notify-success' => 'false',
        'schedule-reminder' => [],
        'schedule-recipient' => [
            'ci@repomanager.net'
        ],
    ],
];

// Change dir
chdir(ROOT . '/templates/source-repositories');

// Get all source repository templates
$templates = glob('*/*.yml');

// Rename all templates to add github prefix and remove the .yml extension
foreach ($templates as $template) {
    // If the template has 'redhat' in its name, skip it and remove it from the list
    if (strpos($template, 'redhat') !== false) {
        unset($templates[array_search($template, $templates)]);
        continue;
    }

    $newName = str_replace('.yml', '', 'github/' . $template);
    $templates[array_search($template, $templates)] = $newName;
}

// Import
$sourceController->import($templates);

// Get all imported sources
$debSources = $sourceController->listAll('deb');
$rpmSources = $sourceController->listAll('rpm');

// TODO debug
// For each deb source, create a task
// foreach ($debSources as $source) {
//     $tasks = [];
//     $id = $source['Id'];

//     try {
//         $source = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
//     } catch (JsonException $e) {
//         throw new Exception('failed to decode JSON for source #' . $id);
//     }

//     foreach ($source['distributions'] as $distribution) {
//         foreach ($distribution['components'] as $component) {
//             $tasks = [];
//             $params = $debParamsTemplate;
//             $params['source']    = $source['name'];
//             $params['alias']     = $source['name'];
//             $params['dist'][]    = $distribution['name'];
//             $params['section'][] = $component['name'];
//             $params['schedule']['schedule-date'] = date('Y-m-d');
//             $params['schedule']['schedule-time'] = date('H:i', strtotime('+5 minutes')); // Now +5 minutes

//             // Add the task parameters to the tasks array
//             $tasks[] = $params;

//             // Create a task for the deb source distribution component
//             $taskController->execute($tasks);
//         }
//     }
// }

// TODO debug
$counter = 0;

// For each rpm source, create a task
foreach ($rpmSources as $source) {
    $tasks = [];
    $id = $source['Id'];

    try {
        $source = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('failed to decode JSON for source #' . $id);
    }

    foreach ($source['releasever'] as $releasever) {
        $tasks = [];
        $params = $rpmParamsTemplate;
        $params['source']       = $source['name'];
        $params['alias']        = $source['name'];
        $params['releasever'][] = $releasever['name'];
        $params['schedule']['schedule-date'] = date('Y-m-d');
        $params['schedule']['schedule-time'] = date('H:i', strtotime('+5 minutes')); // Now +5 minutes

        // Add the task parameters to the tasks array
        $tasks[] = $params;

        // Create a task for the rpm source releasever
        $taskController->execute($tasks);
    }

    $counter++;
    // Stop if counter has reached 10
    if ($counter >= 10) {
        break;
    }
}
