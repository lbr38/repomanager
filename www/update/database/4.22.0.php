<?php
/**
 *  4.22.0 update
 */

/**
 *  Add 'SESSION_TIMEOUT' column to settings table
 */
if (!$this->db->columnExist('settings', 'SESSION_TIMEOUT')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN SESSION_TIMEOUT INTEGER DEFAULT 3600");
}

/**
 *  Add 'RPM_SIGNATURE_FAIL' column to settings table
 */
if (!$this->db->columnExist('settings', 'RPM_SIGNATURE_FAIL')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN RPM_SIGNATURE_FAIL VARCHAR(255) DEFAULT 'error'");
}

/**
 *  Create user_permissions table
 */
$this->db->exec("CREATE TABLE IF NOT EXISTS user_permissions (
Permissions VARCHAR(255),
User_id INTEGER NOT NULL)");
