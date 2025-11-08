<?php
// Create task configuration
$taskConfig = [
    'description' => 'Create repository snapshot',

    // Do not retrieve repository info from snap id (because it does not exist yet)
    'retrieve-repo-from-snap-id' => false,

    // Required params
    'required-params' => [
        'package-type',
        'repo-type',
        'arch'
    ],

    // Optional params
    'optional-params' => [
        'env',
        'group',
        'description',
        'package-include',
        'package-exclude'
    ],

    // Some required params are conditional
    'conditional-required-params' => [
        // Based on package type
        'package-type' => [
            'rpm' => [
                'releasever'
            ],
            'deb' => [
                'dist',
                'section'
            ]
        ],

        // Based on repo type
        'repo-type' => [
            'mirror' => [
                'source',
                'gpg-check',
                'gpg-sign'
            ]
        ]
    ],

    // Conditional params must be compared with form values
    'conditional-compare-with' => 'form'
];
