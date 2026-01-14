<?php
$appDatabases = [
    'main' => [
        'title' => 'Main Database',
        'description' => 'Storing repositories, users, permissions and more.',
        'path' => DB,
    ],

    'stats' => [
        'title' => 'Stats Database',
        'description' => 'Storing repositories access statistics.',
        'path' => STATS_DB,
    ],

    'hosts' => [
        'title' => 'Hosts Database',
        'description' => 'Storing hosts and their information.',
        'path' => HOSTS_DB,
    ],

    'ws' => [
        'title' => 'Websocket Database',
        'description' => 'Storing websocket connections and their information.',
        'path' => WS_DB,
    ],
];

foreach ($appDatabases as $name => $db) {
    // Check if file exists
    if (!file_exists($db['path'])) {
        $appDatabases[$name]['errors'][] = $db['path'] . ' database file is missing';
    }

    // Check if file is readable
    if (!is_readable($db['path'])) {
        $appDatabases[$name]['errors'][] = $db['path'] . ' database file is not readable';
    }

    // Check if file is writable
    if (!is_writable($db['path'])) {
        $appDatabases[$name]['errors'][] = $db['path'] . ' database file is not writable';
    }

    // Check that all tables are present
    $cn = new \Models\Connection($name);
    $method = 'check' . ucfirst($name) . 'Tables';
    $cn->$method();

    $appDatabases[$name]['required'] = $cn->required;
    $appDatabases[$name]['count'] = $cn->count;

    // Store total tables info
    $appDatabases[$name]['total'] = $cn->count . '/' . $cn->required;

    // If tables are missing, calculate how many
    if ($cn->count < $cn->required) {
        $missingTables = $cn->required - $cn->count;
        $appDatabases[$name]['errors'][] = $missingTables . ' table(s) missing';
    }

    // If there are more tables than required, calculate how many
    if ($cn->count > $cn->required) {
        $extraTables = $cn->count - $cn->required;
        $appDatabases[$name]['errors'][] = $extraTables . ' extra table' . ($extraTables > 1 ? 's' : '') . ' found';
    }
}

unset($name, $db, $cn, $method, $missingTables, $extraTables);
