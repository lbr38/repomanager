<?php
$tasksDefinitions = [
    'create' => [
        'description' => 'Create repository snapshot',
        'required-params' => [
            'package-type',
            'repo-type',
            'arch',

            // 'repo-id',
            // 'gpg-sign'
        ],
        // Some required params are conditional
        'conditional-required-params' => [
            // Based on package type
            'package-type' => [
                'rpm' => [
                    'required-params' => [
                        'releasever'
                    ],
                    'optional-params' => []
                ],
                'deb' => [
                    'required-params' => [
                        'dist',
                        'section'
                    ],
                    'optional-params' => []
                ]
            ],
            // Based on repo type
            'repo-type' => [
                'mirror' => [
                    'required-params' => [
                        'source',
                        'gpg-check',
                        'gpg-sign'
                    ],
                    'optional-params' => []
                ]
            ]
        ],

        'optional-params' => [
            'env',
            'group',
            'description',
            'package-include',
            'package-exclude'
        ],
        'retrieve-repo-from-snap-id' => false
    ],


    'delete' => [
        'description' => 'Delete repository snapshot',
        'required-params' => [
            'snap-id'
        ],
        'optional-params' => [],
        'retrieve-repo-from-snap-id' => true
    ],
    'rebuild' => [
        'description' => 'Rebuild repository metadata',
        'required-params' => [
            'snap-id',
            'gpg-sign'
        ],
        'optional-params' => [],
        'retrieve-repo-from-snap-id' => true
    ]
];
