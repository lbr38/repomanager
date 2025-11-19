<?php
// Rebuild task configuration
$taskConfig = [
    'description' => 'Rebuild repository metadata',

    // Retrieve repository info from snap id
    'retrieve-repo-from-snap-id' => true,

    // Required params
    'required-params' => [
        'snap-id',
        'gpg-sign'
    ]
];
