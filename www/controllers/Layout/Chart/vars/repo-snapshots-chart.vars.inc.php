<?php
use \Controllers\Filesystem\Directory;

$repoController = new \Controllers\Repo\Repo();
$datasets = [];
$labels = [];
$options = [];

if (__ACTUAL_URI__[2] != 'repo') {
    throw new Exception('Error: invalid URI specified.');
}

if (empty(__ACTUAL_URI__[3])) {
    throw new Exception('Error: missing repository ID.');
}

if (!is_numeric(__ACTUAL_URI__[3])) {
    throw new Exception('Error: invalid repository ID specified.');
}

// Get repository info
$repoController->getAllById(__ACTUAL_URI__[3]);

// Get all snapshots of the repository
$snapshots = $repoController->getSnapshots($repoController->getRepoId());

// Initialize dataset with proper name
$datasets[0] = [
    'name' => 'Snapshot size (MB)',
    'data' => [],
    'snapshotIds' => [] // Store snapshot IDs for click handling
];

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
    'labelColor' => '#FFFFFF',
    'labelFontSize' => 12,
    'labelFontWeight' => 'bold',
    'labelFontFamily' => 'Arial, sans-serif',
    'labelBackground' => 'rgb(46, 54, 58)',
    'labelBorder' => false,
    'labelBorderRadius' => 16,
    'labelPadding' => [6, 15],

    // Drop shadow (optional)
    'labelShadow' => true,

    // Date format
    'labelDateFormat' => 'fr-FR', // Locale for dates
    'labelDateSeparator' => '-', // Date separator ('-' or '/' or '.')
    'labelDateOptions' => [ // Date formatting options
        'day' => '2-digit',
        'month' => '2-digit',
        'year' => 'numeric'
    ],

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
        'url' => '/browse/{value}',
        'newTab' => false // '_self' for same tab, '_blank' for new tab
    ]
];

unset($snapshotPath, $size, $snapshot, $snapshots, $repoController);
