<?php
/**
 *  3.4.18 database update
 */

/**
 *  Check that stats database exists
 */
if (!is_file(STATS_DB)) {
    return;
}

/**
 *  Open stats database
 */
$statsDb = new \Models\Connection('stats');

/**
 *  Quit if 'stats' table does not exist
 */
if ($statsDb->tableExist('stats') !== true) {
    return;
}

/**
 *  Retrieve stats
 */
$stats = array();
$result = $statsDb->query("SELECT * FROM stats");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $stats[] = $row;
}

/**
 *  Quit if stats table is empty
 */
if (empty($stats)) {
    return;
}

/**
 *  Update stats database
 */
foreach ($stats as $stat) {
    // Convert size in kylobytes to bytes
    $newSize = $stat['Size'] * 1024;

    // Update stats database
    $statsDb->exec("UPDATE stats SET Size = '" . $newSize . "' WHERE Id = '" . $stat['Id'] . "'");
}

$statsDb->close();

if (!is_dir(DATA_DIR . '/update')) {
    mkdir(DATA_DIR . '/update', 0770, true);
}

if (!file_exists(DATA_DIR . '/update/3.4.18.done')) {
    touch(DATA_DIR . '/update/3.4.18.done');
}
