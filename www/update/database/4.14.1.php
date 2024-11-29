<?php
/**
 *  4.14.1 update
 */

/**
 *  Open hosts database
 */
$hostsDb = new \Models\Connection('hosts');

/**
 *  Delete 'Id' column from 'layout_container_state' table
 */
if ($this->db->columnExist('layout_container_state', 'Id') === true) {
    $this->db->exec("ALTER TABLE layout_container_state DROP COLUMN Id");
    $this->db->exec("VACUUM");
}

/**
 *  Move 'ws_requests' table to 'requests' table
 */
if ($hostsDb->tableExist('ws_requests') === true) {
    $hostsDb->exec("INSERT INTO requests SELECT * FROM ws_requests");
    $hostsDb->exec("DROP TABLE IF EXISTS ws_requests");
    $hostsDb->exec("DROP INDEX IF EXISTS ws_requests_status");
    $hostsDb->exec("DROP INDEX IF EXISTS ws_requests_date_time");
    $hostsDb->exec("DROP INDEX IF EXISTS ws_requests_id_host");
}

/**
 *  Drop table 'ws_connections' from hosts database
 */
if ($hostsDb->tableExist('ws_connections') === true) {
    $hostsDb->exec("DROP TABLE IF EXISTS ws_connections");
}

/**
 *  Clean up
 */
$hostsDb->exec("VACUUM");
$hostsDb->exec("ANALYZE");
$hostsDb->close();

unset($hostsDb);
