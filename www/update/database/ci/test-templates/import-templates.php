<?php
use \Controllers\Task\Task;
use \Controllers\Repo\Source\Source;

// Schedule for tasks in minutes
$minutesSchedule = 5;

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
    'arch' => [],
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
            'exclude' => []
        ],
        'metadata-custom-fields' => [
            'origin' => '',
            'label' => '',
            'description' => ''
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
    'arch' => [],
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
            'exclude' => []
        ],
        'metadata-sync' => [
            'comps' => 'true',
            'modules' => 'true',
            'updateinfo' => 'true'
        ]
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

// Deb repositories to ignore from import / test
$ignoreDebRepositories = [
    'pve-enterprise' // Requires a subscription
];

// Rpm repositories to ignore from import / test
$ignoreRpmRepositories = [
    'redhat-baseos',   // Requires a subscription
    'redhat-appstream' // Requires a subscription
];

// Change dir
chdir(ROOT . '/templates/source-repositories');

// Get all source repository templates
$templates = glob('*/*.yml');

// Rename all templates to add github prefix and remove the .yml extension
foreach ($templates as $template) {
    $newName = str_replace('.yml', '', 'github/' . $template);
    $templates[array_search($template, $templates)] = $newName;
}

// Import
$sourceController->import($templates);

// Get all imported sources
$debSources = $sourceController->listAll('deb');
$rpmSources = $sourceController->listAll('rpm');

// For each deb source, create a task
foreach ($debSources as $source) {
    $id = $source['Id'];

    try {
        $source = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('failed to decode JSON for source #' . $id);
    }

    if (empty($source['distributions'])) {
        throw new Exception('Source #' . $id . ' has no distributions, skipping.');
    }

    foreach ($source['distributions'] as $distribution) {
        foreach ($distribution['components'] as $component) {
            // Skip repositories that are in the ignore list
            if (in_array($source['name'], $ignoreDebRepositories)) {
                continue;
            }

            $params = $debParamsTemplate;
            $params['source']    = $source['name'];
            $params['alias']     = $source['name'];
            $params['dist'][]    = $distribution['name'];
            $params['section'][] = $component['name'];
            $params['arch']      = $component['archs'] ?? ['amd64']; // Use predefined architecture if defined, otherwise default to 'amd64'
            $params['schedule']['schedule-date'] = date('Y-m-d');
            $params['schedule']['schedule-time'] = date('H:i', strtotime('+' . strval($minutesSchedule) . ' minutes')); // Now +$minutesSchedule minutes

            // Create a task for the deb source distribution component
            $taskController->execute([$params]);
        }
    }
}

// For each rpm source, create a task
foreach ($rpmSources as $source) {
    $id = $source['Id'];

    try {
        $source = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('failed to decode JSON for source #' . $id);
    }

    // Skip repos that are in the ignore list
    if (in_array($source['name'], $ignoreRpmRepositories)) {
        continue;
    }

    foreach ($source['releasever'] as $releasever) {
        $params = $rpmParamsTemplate;
        $params['source']       = $source['name'];
        $params['alias']        = $source['name'];
        $params['releasever'][] = $releasever['name'];
        $params['arch']         = $releasever['archs'] ?? ['x86_64', 'noarch']; // Use predefined architecture if defined, otherwise default to 'x86_64'
        // Always append 'noarch' to the predefined archs, because some packages may be published as noarch in /<arch>/ directories
        $params['arch']         = array_unique(array_merge($params['arch'], ['noarch']));
        $params['schedule']['schedule-date'] = date('Y-m-d');
        $params['schedule']['schedule-time'] = date('H:i', strtotime('+' . strval($minutesSchedule) . ' minutes')); // Now +$minutesSchedule minutes

        // Create a task for the rpm source releasever
        $taskController->execute([$params]);
    }
}
