<?php
// Create task configuration
$taskConfig = [
    'description' => 'Point environment on repository',

    // Retrieve repository info from snap id
    'retrieve-repo-from-snap-id' => true,

    // Required params
    'required-params' => [
        'snap-id',
        'env'
    ],

    // Optional params
    'optional-params' => [
        'description'
    ],

    // Conditional params must be compared with form values
    'conditional-compare-with' => 'form'
];

// Form configuration
$formConfig = [
    // Allowed schedule types for this task
    // Recurring is only allowed when the task targets a dynamic set of repositories,
    // as pointing an environment on a fixed snapshot repeatedly makes no sense
    'allowed-schedule-types' => ['unique'],

    // This task can target a dynamic set of repositories (all latest snapshots matching filters)
    'allow-dynamic-target' => true
];
