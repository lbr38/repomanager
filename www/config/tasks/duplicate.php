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
    'optional-params' => [
        'env',
        'group',
        'description',
    ],

    // Conditional params must be compared with form values
    'conditional-compare-with' => 'form'
];
