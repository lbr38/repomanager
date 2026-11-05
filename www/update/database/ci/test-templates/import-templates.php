<?php
use \Controllers\Task\Task;
use \Controllers\Repo\Source\Source;

// Schedule for tasks in minutes
$minutesSchedule = 5;

$sourceController = new Source();
$taskController = new Task();
$debParamsTemplate = [
    'action' => 'create',
    'package-type' => 'deb',
    'repo-type' => 'filtered',
    'source' => '',
    'alias' => '',
    'dist' => [],
    'section' => [],
    'arch' => [],
    'env' => [
        'preprod'
    ],
    'description' => '',
    'tags' => [],
    'gpg-check' => 'true',
    'gpg-sign' => 'false',
    'advanced-params' => [
        'packages' => [
            'keep-latest' => '1',
            'include' => ['aaaaaaa.*'],
            'exclude' => []
        ],
        'metadata-custom-fields' => [
            'origin' => '',
            'label' => '',
            'description' => ''
        ],
    ],

    'schedule' => [
        'scheduled' => 'true',
        'schedule-type' => 'unique',
        'schedule-date' => '',
        'schedule-time' => '',
        'schedule-notify-error' => 'true',
        'schedule-notify-success' => 'false',
        'schedule-reminder' => [],
        'schedule-recipient' => [
            'ci@repomanager.net'
        ],
    ],
];
$rpmParamsTemplate = [
    'action' => 'create',
    'package-type' => 'rpm',
    'repo-type' => 'filtered',
    'source' => '',
    'alias' => '',
    'releasever' => [],
    'arch' => [],
    'env' => [
        'preprod'
    ],
    'description' => '',
    'tags' => [],
    'gpg-check' => 'true',
    'gpg-sign' => 'false',
    'advanced-params' => [
        'packages' => [
            'keep-latest' => '1',
            'include' => ['aaaaaaa.*'],
            'exclude' => []
        ],
        'metadata-sync' => [
            'comps' => 'true',
            'modules' => 'true',
            'updateinfo' => 'true'
        ]
    ],

    'schedule' => [
        'scheduled' => 'true',
        'schedule-type' => 'unique',
        'schedule-date' => '',
        'schedule-time' => '',
        'schedule-notify-error' => 'true',
        'schedule-notify-success' => 'false',
        'schedule-reminder' => [],
        'schedule-recipient' => [
            'ci@repomanager.net'
        ],
    ],
];

// Mirrors: arch source repositories can only be used by mirrors, some deb and rpm source repositories are also tested as mirrors
$mirrorParamsTemplate = [
    'action' => 'create',
    'domain' => 'mirror',
    'package-type' => '',
    'name' => '',
    'source' => '',
    'arch' => [],
    'env' => [
        'preprod'
    ],
    'description' => '',
    'tags' => [],
    'gpg-check' => 'true',
    // Mirrors have no packages filter: only the metadata are retrieved, mirroring all the packages would be too large
    'metadata-only' => 'true',

    'schedule' => [
        'scheduled' => 'true',
        'schedule-type' => 'unique',
        'schedule-date' => '',
        'schedule-time' => '',
        'schedule-notify-error' => 'true',
        'schedule-notify-success' => 'false',
        'schedule-reminder' => [],
        'schedule-recipient' => [
            'ci@repomanager.net'
        ],
    ],
];

// Deb repositories to ignore from import / test
$ignoreDebRepositories = [
    'pve-enterprise' // Requires a subscription
];

// Rpm repositories to ignore from import / test
$ignoreRpmRepositories = [
    'redhat-baseos',   // Requires a subscription
    'redhat-appstream' // Requires a subscription
];

// Arch repositories to ignore from import / test
$ignoreArchRepositories = [];

/**
 *  Deb and rpm mirrors to test, each one covering a different case (all deb and rpm source repositories are tested above as filtered repositories)
 *  Only the metadata are retrieved, unless 'metadata-only' is set to 'false' (small repositories only)
 */
$mirrorCases = [
    // Several distributions sharing the same pool, several components and architectures
    [
        'package-type' => 'deb',
        'name' => 'debian-trixie',
        'source' => 'debian',
        'dist' => ['trixie', 'trixie-updates'],
        'components' => ['main', 'contrib'],
        'arch' => ['amd64', 'arm64']
    ],
    // Previous release, signed with other keys
    [
        'package-type' => 'deb',
        'name' => 'debian-bookworm',
        'source' => 'debian',
        'dist' => ['bookworm', 'bookworm-updates'],
        'components' => ['main'],
        'arch' => ['amd64']
    ],
    // Other URL, distribution named with a suffix
    [
        'package-type' => 'deb',
        'name' => 'debian-security-trixie',
        'source' => 'debian-security',
        'dist' => ['trixie-security'],
        'components' => ['main'],
        'arch' => ['amd64']
    ],
    // Third-party repository
    [
        'package-type' => 'deb',
        'name' => 'nginx-trixie',
        'source' => 'nginx',
        'dist' => ['trixie'],
        'components' => ['nginx'],
        'arch' => ['amd64', 'arm64']
    ],
    // $releasever and $basearch variables in the URL, one directory per architecture, signed repomd.xml
    [
        'package-type' => 'rpm',
        'name' => 'almalinux-baseos',
        'source' => 'almalinux-baseos',
        'releasever' => '10',
        'arch' => ['x86_64', 'aarch64']
    ],
    // Modules metadata (release 10 has no modules)
    [
        'package-type' => 'rpm',
        'name' => 'almalinux-appstream',
        'source' => 'almalinux-appstream',
        'releasever' => '9',
        'arch' => ['x86_64']
    ],
    // Third-party repository
    [
        'package-type' => 'rpm',
        'name' => 'nginx',
        'source' => 'nginx',
        'releasever' => '10',
        'arch' => ['x86_64', 'aarch64']
    ],
    // Small repository, entirely mirrored (packages included)
    [
        'package-type' => 'rpm',
        'name' => 'almalinux-extras',
        'source' => 'almalinux-extras',
        'releasever' => '10',
        'arch' => ['x86_64'],
        'metadata-only' => 'false'
    ],
];

// Change dir
chdir(ROOT . '/templates/source-repositories');

// Get all source repository templates
$templates = glob('*/*.yml');

// Rename all templates to add github prefix and remove the .yml extension
foreach ($templates as $template) {
    $newName = str_replace('.yml', '', 'github/' . $template);
    $templates[array_search($template, $templates)] = $newName;
}

// Import
$sourceController->import($templates);

// Get all imported sources
$debSources = $sourceController->listAll('deb');
$rpmSources = $sourceController->listAll('rpm');
$archSources = $sourceController->listAll('arch');

// For each deb source, create a task
foreach ($debSources as $source) {
    $id = $source['Id'];

    try {
        $source = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('failed to decode JSON for source #' . $id);
    }

    if (empty($source['distributions'])) {
        throw new Exception('Source #' . $id . ' has no distributions, skipping.');
    }

    foreach ($source['distributions'] as $distribution) {
        foreach ($distribution['components'] as $component) {
            // Skip repositories that are in the ignore list
            if (in_array($source['name'], $ignoreDebRepositories)) {
                continue;
            }

            $params = $debParamsTemplate;
            $params['source']    = $source['name'];
            $params['alias']     = $source['name'];
            $params['dist'][]    = $distribution['name'];
            $params['section'][] = $component['name'];
            $params['arch']      = $component['archs'] ?? ['amd64']; // Use predefined architecture if defined, otherwise default to 'amd64'
            $params['schedule']['schedule-date'] = date('Y-m-d');
            $params['schedule']['schedule-time'] = date('H:i', strtotime('+' . strval($minutesSchedule) . ' minutes')); // Now +$minutesSchedule minutes

            // Create a task for the deb source distribution component
            $taskController->execute([$params]);
        }
    }
}

// For each rpm source, create a task
foreach ($rpmSources as $source) {
    $id = $source['Id'];

    try {
        $source = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('failed to decode JSON for source #' . $id);
    }

    // Skip repos that are in the ignore list
    if (in_array($source['name'], $ignoreRpmRepositories)) {
        continue;
    }

    foreach ($source['releasever'] as $releasever) {
        $params = $rpmParamsTemplate;
        $params['source']       = $source['name'];
        $params['alias']        = $source['name'];
        $params['releasever'][] = $releasever['name'];
        $params['arch']         = $releasever['archs'] ?? ['x86_64', 'noarch']; // Use predefined architecture if defined, otherwise default to 'x86_64'
        // Always append 'noarch' to the predefined archs, because some packages may be published as noarch in /<arch>/ directories
        $params['arch']         = array_unique(array_merge($params['arch'], ['noarch']));
        $params['schedule']['schedule-date'] = date('Y-m-d');
        $params['schedule']['schedule-time'] = date('H:i', strtotime('+' . strval($minutesSchedule) . ' minutes')); // Now +$minutesSchedule minutes

        // Create a task for the rpm source releasever
        $taskController->execute([$params]);
    }
}

// For each arch source, create a mirror task per architecture, containing all the repositories of the source
foreach ($archSources as $source) {
    $id = $source['Id'];

    try {
        $source = json_decode($source['Definition'], true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        throw new Exception('failed to decode JSON for source #' . $id);
    }

    // Skip repos that are in the ignore list
    if (in_array($source['name'], $ignoreArchRepositories)) {
        continue;
    }

    if (empty($source['repos'])) {
        throw new Exception('Source #' . $id . ' has no repositories, skipping.');
    }

    // Use predefined architectures if defined, otherwise default to 'x86_64'
    $archs = !empty($source['archs']) ? array_column($source['archs'], 'name') : ['x86_64'];

    foreach ($archs as $arch) {
        $params = $mirrorParamsTemplate;
        $params['package-type'] = 'arch';
        $params['name']   = $source['name'] . '-' . $arch; // Mirrors names are unique, one mirror per architecture
        $params['source'] = $source['name'];
        $params['repos']  = array_column($source['repos'], 'name');
        $params['arch'][] = $arch;
        $params['schedule']['schedule-date'] = date('Y-m-d');
        $params['schedule']['schedule-time'] = date('H:i', strtotime('+' . strval($minutesSchedule) . ' minutes')); // Now +$minutesSchedule minutes

        // Create a mirror task for the arch source architecture
        $taskController->execute([$params]);
    }
}

// For each deb and rpm mirror case, create a mirror task
foreach ($mirrorCases as $mirrorCase) {
    $params = array_merge($mirrorParamsTemplate, $mirrorCase);
    $params['schedule']['schedule-date'] = date('Y-m-d');
    $params['schedule']['schedule-time'] = date('H:i', strtotime('+' . strval($minutesSchedule) . ' minutes')); // Now +$minutesSchedule minutes

    // Create a mirror task for the deb or rpm source repository
    $taskController->execute([$params]);
}
