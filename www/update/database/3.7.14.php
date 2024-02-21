<?php
/**
 *  3.7.14 database update
 */

if (!file_exists(STATS_DB)) {
    return;
}

/**
 *  Open stats database
 */
$statsDb = new \Models\Connection('stats');

/**
 *  Quit if 'access' table does not exist
 */
if (!$statsDb->tableExist('access')) {
    $statsDb->close();
    return;
}

/**
 *  Drop old access table
 */
$statsDb->exec("DROP TABLE IF EXISTS access");

/**
 *  Vacuum and quit
 */
$statsDb->exec("VACUUM");
$statsDb->exec("ANALYZE");
$statsDb->close();
