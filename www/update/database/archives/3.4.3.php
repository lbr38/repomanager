<?php
/**
 *  3.4.3 database update
 */

if (!file_exists(STATS_DB)) {
    return;
}

/**
 *  Open stats database
 */
$statsDb = new \Models\Connection('stats');

/**
 *  Destroy and recreate indexes
 */
$statsDb->exec("DROP INDEX IF EXISTS request_index");
$statsDb->exec("CREATE INDEX IF NOT EXISTS access_index ON access (Date, Time, Request)");
$statsDb->exec("CREATE INDEX IF NOT EXISTS stats_index ON stats (Date, Time, Size, Packages_count, Id_env)");

$statsDb->close();

unset($statsDb);
