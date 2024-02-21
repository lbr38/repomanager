<?php
/**
 *  3.4.1 database update
 */

if (!file_exists(HOSTS_DB)) {
    return;
}

/**
 *  hosts db must not be empty
 */
clearstatcache();
if (!filesize(HOSTS_DB)) {
    return;
}

/**
 *  Open hosts database
 */
$hostsDb = new \Models\Connection('hosts');

/**
 *  Check if Allow_overwrite column exists in profile table
 */
if ($hostsDb->columnExist('hosts', 'Linupdate_version') === true) {
    return;
}

/**
 *  Add Linupdate_version column
 */
$hostsDb->exec("ALTER TABLE hosts ADD Linupdate_version VARCHAR(255)");

$hostsDb->close();

unset($hostsDb);
