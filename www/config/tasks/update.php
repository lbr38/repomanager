<?php
// Update task configuration
$taskConfig = [
    'description' => 'Update repository',

    // Retrieve repository info from snap id
    'retrieve-repo-from-snap-id' => true,

    // Force the use of the latest snapshot Id for the repository to make sure to update from something existing
    // Useful for recurring tasks
    'use-latest-snapshot' => true,

    // Required params
    'required-params' => [
        'repo-id',
        'snap-id',
    ],

    // Optional params
    'optional-params' => [
        'env'
    ],

    // Some required params are conditional
    'conditional-required-params' => [
        // Based on repo type
        'repo-type' => [
            'mirror' => [
                'arch',
                'gpg-check',
                'gpg-sign'
            ],
            'local' => [
                'arch',
                'gpg-sign'
            ]

        ]
    ],

    // Some optional params are conditional
    'conditional-optional-params' => [
        // Based on repo type
        'repo-type' => [
            'mirror' => [
                'package-include',
                'package-exclude'
            ]
        ]
    ],

    // Conditional params must be compared with current repository values
    'conditional-compare-with' => 'current-repo'
];
