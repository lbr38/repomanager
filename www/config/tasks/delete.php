<?php
// Delete task configuration
$taskConfig = [
    'description' => 'Delete repository snapshot',

    // Retrieve repository info from snap id
    'retrieve-repo-from-snap-id' => true,

    // Required params
    'required-params' => [
        'snap-id'
    ]
];

// Form configuration
$formConfig = [
    // Allowed schedule types for this task
    'allowed-schedule-types' => ['unique']
];
