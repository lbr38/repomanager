<?php
// Create task configuration
$taskConfig = [
    'description' => 'Rename repository',

    // Retrieve repository info from repo id
    'retrieve-repo-from-repo-id' => true,

    // Required params
    'required-params' => [
        'name',
        'repo-id',
        // 'gpg-sign' // Required to make sure deb metadata is regenerated correctly
    ],

    // Conditional params must be compared with form values
    'conditional-compare-with' => 'form'
];

// Form configuration
$formConfig = [
    // Allowed schedule types for this task
    'allowed-schedule-types' => ['unique']
];
