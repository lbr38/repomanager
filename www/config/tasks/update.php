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
        'env',
        'advanced-params' // Advanced params include package include/exclude and metadata custom fields, it is optional
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

    // Conditional params must be compared with current repository values
    'conditional-compare-with' => 'current-repo',
];

// Form configuration
$formConfig = [
    // Allowed schedule types for this task
    'allowed-schedule-types' => ['unique', 'recurring']
];
