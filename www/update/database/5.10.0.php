<?php
/**
 *  5.10.0 update
 */
$hostsDb = new \Models\Connection('hosts');

// Drop old indexes names
try {
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_authid_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_token_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_authid_token_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_hostname_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_kernel_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_profile_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_status_online_date_time");
    $hostsDb->exec("DROP INDEX IF EXISTS groups_index");
    $hostsDb->exec("DROP INDEX IF EXISTS group_members_index");
    $hostsDb->exec("DROP INDEX IF EXISTS group_members_id_host_index");
    $hostsDb->exec("DROP INDEX IF EXISTS group_members_id_group_index");
    $hostsDb->exec("DROP INDEX IF EXISTS requests_id_host");
    $hostsDb->exec("DROP INDEX IF EXISTS requests_status");
    $hostsDb->exec("DROP INDEX IF EXISTS requests_date_time");
} catch (Exception $e) {
    throw new Exception('could not delete old indexes from hosts database: ' . $e->getMessage());
}
