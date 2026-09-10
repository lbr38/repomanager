<?php
// Rebuild task configuration
$taskConfig = [
    'description' => 'Rebuild repository metadata',

    // Retrieve repository info from snap id
    'retrieve-repo-from-snap-id' => true,

    // Required params
    'required-params' => [
        'snap-id',
        'gpg-sign'
    ]
];

// Form configuration
$formConfig = [
    // Allowed schedule types for this task
    'allowed-schedule-types' => ['unique', 'recurring'],

    // This task can target a dynamic set of repositories (all latest snapshots matching filters)
    'allow-dynamic-target' => true
];
