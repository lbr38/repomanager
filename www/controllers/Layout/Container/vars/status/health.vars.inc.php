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

    if ($cn->$method() === false) {
        $appDatabases[$name]['errors'][] = 'One or more table are missing';
    }
}

unset($name, $db, $cn, $method);
