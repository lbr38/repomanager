<?php
// Create task configuration
$taskConfig = [
    'description' => 'Rename repository',

    // Retrieve repository info from snap id
    'retrieve-repo-from-snap-id' => true,

    // Required params
    'required-params' => [
        'name',
        'snap-id',
        'gpg-sign' // Required to make sure deb metadata is regenerated correctly
    ],

    // Conditional params must be compared with form values
    'conditional-compare-with' => 'form'
];
