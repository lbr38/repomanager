<?php
// Create task configuration
$taskConfig = [
    'description' => 'Duplicate repository snapshot',

    // Retrieve repository info from snap id
    'retrieve-repo-from-snap-id' => true,

    // Required params
    'required-params' => [
        'name',
        'snap-id',
        'gpg-sign',
        'arch'
    ],

    // Optional params
    // releasever (rpm) and dist (deb) of the new repository, the same as the source snapshot if not specified
    'optional-params' => [
        'releasever',
        'dist',
        'env',
        'group',
        'description',
        'tags'
    ],

    // Conditional params must be compared with form values
    'conditional-compare-with' => 'form'
];

// Form configuration
$formConfig = [
    // Allowed schedule types for this task
    'allowed-schedule-types' => ['unique']
];
