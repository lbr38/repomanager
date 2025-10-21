<?php
/**
 *  5.3.3 update
 */

$hostController = new \Controllers\Host();

/**
 *  Get all hosts
 */
$hosts = $hostController->listAll();

foreach ($hosts as $host) {
    // Ignore hosts if database does not exist (should not happen)
    if (!file_exists(HOSTS_DIR . '/' . $host['Id'] . '/properties.db')) {
        continue;
    }

    // Open host database
    $hostDb = new \Models\Connection('host', $host['Id']);

    // Add Repository column to packages_available table
    if (!$hostDb->columnExist('packages_available', 'Repository')) {
        $hostDb->exec("ALTER TABLE packages_available ADD COLUMN Repository VARCHAR(255)");
    }

    // Add Command column to events table
    if (!$hostDb->columnExist('events', 'Command')) {
        $hostDb->exec("ALTER TABLE events ADD COLUMN Command VARCHAR(255)");
    }

    // Delete Report column from events table
    if ($hostDb->columnExist('events', 'Report')) {
        $hostDb->exec("ALTER TABLE events DROP COLUMN Report");
    }

    // Close host database
    $hostDb->close();
}
