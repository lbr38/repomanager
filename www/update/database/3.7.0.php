<?php
/**
 *  3.7.0 database update
 */

/**
 *  Add cve columns in settings table
 */
if ($this->db->columnExist('settings', 'CVE_IMPORT') === true) {
    return;
}

/**
 *  Add new cve columns in settings
 */
$this->db->exec("ALTER TABLE settings ADD CVE_IMPORT CHAR(5)");
$this->db->exec("ALTER TABLE settings ADD CVE_IMPORT_TIME TIME");
$this->db->exec("ALTER TABLE settings ADD CVE_SCAN_HOSTS CHAR(5)");

/**
 *  Set value for new columns
 */
$this->db->exec("UPDATE settings SET CVE_IMPORT = 'false'");
$this->db->exec("UPDATE settings SET CVE_IMPORT_TIME = '00:00'");
$this->db->exec("UPDATE settings SET CVE_SCAN_HOSTS = 'false'");
