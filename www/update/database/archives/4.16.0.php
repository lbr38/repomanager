<?php
/**
 *  4.16.0 update
 */

/**
 *  Delete 'ssl' directory if exists
 */
if (is_dir(DATA_DIR . '/ssl')) {
    \Controllers\Filesystem\Directory::deleteRecursive(DATA_DIR . '/ssl');
}

/**
 *  Open hosts database
 */
$hostsDb = new \Models\Connection('hosts');

/**
 *  Retrieve all 'deleted' hosts
 */
$deletedHosts = [];
$result = $hostsDb->query("SELECT Id FROM hosts WHERE Status = 'deleted'");

while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
    $deletedHosts[] = $row['Id'];
}

if (empty($deletedHosts)) {
    return;
}

/**
 *  Clean all dedicated databases for deleted hosts
 */
foreach ($deletedHosts as $hostId) {
    if (is_dir(HOSTS_DIR . '/' . $hostId)) {
        \Controllers\Filesystem\Directory::deleteRecursive(HOSTS_DIR . '/' . $hostId);
    }
}

/**
 *  Remove all deleted hosts from database
 */
$hostsDb->exec("DELETE FROM hosts WHERE Status = 'deleted'");
