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
