<?php
// Create task configuration
$taskConfig = [
    'description' => 'Remove environment from repository',

    // Retrieve repository info from repo id, snap id and env id
    'retrieve-repo-from-all-id' => true,

    // Required params
    'required-params' => [
        'repo-id',
        'snap-id',
        'env-id'
    ],

    // Conditional params must be compared with form values
    'conditional-compare-with' => 'form'
];
