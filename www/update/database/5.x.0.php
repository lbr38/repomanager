<?php
/**
 *  5.x.0 update
 */

/**
 *  Add 'SYSTEM_USE_NOTIFICATION' column to settings table
 */
if (!$this->db->columnExist('settings', 'SYSTEM_USE_NOTIFICATION')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN SYSTEM_USE_NOTIFICATION VARCHAR(255)");
}
