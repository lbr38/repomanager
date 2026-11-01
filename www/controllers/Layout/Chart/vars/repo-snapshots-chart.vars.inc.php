<?php
use \Controllers\Filesystem\Directory;

$repoController = new \Controllers\Repo\Repo();
$datasets = [];
$labels = [];
$options = [];

// Mirror page (/repository/mirror/<id>): snapshots size is stored in database
$isMirror = (__ACTUAL_URI__[2] ?? '') == 'mirror';

if ($isMirror) {
    $mirror = (new \Controllers\Mirror\Mirror())->getForPage(__ACTUAL_URI__[3] ?? '');
    $datasets[0] = ['name' => 'Snapshot size (MB)', 'data' => [], 'snapshotIds' => []];

    // Most recent first in database, the chart is a timeline
    foreach (array_reverse((new \Controllers\Mirror\Snapshot())->getByMirrorId($mirror['Id'])) as $snapshot) {
        $labels[] = strtotime($snapshot['Date'] . ' ' . $snapshot['Time']) * 1000;
        $datasets[0]['data'][] = (int) $snapshot['Size'] / (1024 * 1024);
        $datasets[0]['snapshotIds'][] = $snapshot['Id'];
    }
}

// Check that repository Id is specified
if (!$isMirror && empty(__ACTUAL_URI__[2])) {
    throw new Exception('no repository ID specified');
}

// Check that repository Id is valid
if (!$isMirror && !is_numeric(__ACTUAL_URI__[2])) {
    throw new Exception('invalid repository ID');
}

// Get repository info and all its snapshots
$snapshots = [];

if (!$isMirror) {
    $repoController->getAllById(__ACTUAL_URI__[2]);
    $snapshots = $repoController->getSnapshots($repoController->getRepoId());
}

// Initialize dataset with proper name
if (!$isMirror) {
    $datasets[0] = [
        'name' => 'Snapshot size (MB)',
        'data' => [],
        'snapshotIds' => [] // Store snapshot IDs for click handling
    ];
}

foreach ($snapshots as $snapshot) {
    $labels[] = strtotime($snapshot['Date']) * 1000;

    if ($repoController->getPackageType() == 'deb') {
        $snapshotPath = REPOS_DIR . '/deb/' . $repoController->getName() . '/' . $repoController->getDist() . '/' . $repoController->getSection() . '/' . $snapshot['Date'];
    } else if ($repoController->getPackageType() == 'rpm') {
        $snapshotPath = REPOS_DIR . '/rpm/' . $repoController->getName() . '/' . $repoController->getReleasever() . '/' . $snapshot['Date'];
    }

    // Get snapshot size in bytes
    $size = Directory::getSize($snapshotPath);

    // Convert it in MB (1 MB = 1024 * 1024 bytes)
    $size = $size / (1024 * 1024);

    $datasets[0]['data'][] = $size;
    $datasets[0]['snapshotIds'][] = $snapshot['Id']; // Store for click handling
}

$options = [
    // Show labels directly on the chart
    'showAsLabels' => true,

    // Labels format and style
    'labelPosition' => 'auto', // 'top', 'bottom', 'left', 'right', 'inside', 'auto' (adaptive)
    'labelColor' => '#c0d0e2',
    'labelFontSize' => 12,
    'labelFontWeight' => '600',
    'labelFontFamily' => 'archivo',
    'labelBackground' => 'transparent',
    'labelBorder' => true,
    'labelBorderColor' => '#c0d0e2',
    'labelBorderWidth' => 0.5,
    'labelBorderRadius' => 20,
    'labelPadding' => [6, 10],

    // Drop shadow (optional)
    'labelShadow' => true,

    // Date format
    'labelDateFormat' => 'fr-FR', // Locale for dates
    'labelDateSeparator' => '-', // Date separator ('-' or '/' or '.')
    'labelDateOptions' => [ // Date formatting options
        'day' => '2-digit',
        'month' => '2-digit',
        'year' => 'numeric'
    ] + ($isMirror ? ['hour' => '2-digit', 'minute' => '2-digit'] : []), // A mirror can have several snapshots a day

    'legend' => [
        'show' => false
    ],
    'toolbox' => [
        'show' => false
    ],
    'tooltip' => [
        'show' => true,
        'valueUnit' => 'MB', // Unit to display after values in tooltip
        'valuePrecision' => 2 // Number of decimals for values
    ],

    // Configure click callback to redirect to snapshot page
    'clickCallback' => [
        'enabled' => true,
        'url' => $isMirror ? '/snapshot/mirror/{value}' : '/snapshot/{value}',
        'newTab' => false // '_self' for same tab, '_blank' for new tab
    ]
];

unset($snapshotPath, $size, $snapshot, $snapshots, $repoController, $mirror, $isMirror);
