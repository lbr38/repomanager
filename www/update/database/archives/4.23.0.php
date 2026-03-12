<?php
/**
 *  4.23.0 update
 */

/**
 *  Open hosts database
 */
$hostsDb = new \Models\Connection('hosts');

/**
 *  Remove 'Status' column from hosts table
 */
if ($hostsDb->columnExist('hosts', 'Status')) {
    // First drop indexes that depend on the Status column
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_authid_token_status_index");
    $hostsDb->exec("DROP INDEX IF EXISTS hosts_status_online_status_date_time");

    // Drop the Status column
    $hostsDb->exec("ALTER TABLE hosts DROP COLUMN Status");

    // Recreate the indexes
    $hostsDb->exec("CREATE INDEX IF NOT EXISTS hosts_index ON hosts (Ip, Hostname, Os, Os_version, Os_family, Kernel, Arch, Type, Profile, Env, AuthId, Token, Online_status, Online_status_date, Online_status_time, Reboot_required, Linupdate_version)");
    $hostsDb->exec("CREATE INDEX IF NOT EXISTS hosts_authid_token_index ON hosts (AuthId, Token)");
    $hostsDb->exec("CREATE INDEX IF NOT EXISTS hosts_status_online_date_time ON hosts (Online_status, Online_status_date, Online_status_time)");
}

/**
 *  Add 'MIRRORING_PACKAGE_CHECKSUM_FAILURE' column to settings table
 */
if (!$this->db->columnExist('settings', 'MIRRORING_PACKAGE_CHECKSUM_FAILURE')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN MIRRORING_PACKAGE_CHECKSUM_FAILURE VARCHAR(20) DEFAULT 'error'");
}
