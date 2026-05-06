<?php
/**
 *  5.6.0 update
 */

/**
 *  Add 'LOGIN_BANNER' column to settings table
 */
if (!$this->db->columnExist('settings', 'LOGIN_BANNER')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN LOGIN_BANNER VARCHAR(255)");
}

/**
 *  Drop 'SYSTEM_USE_NOTIFICATION' column from settings table
 */
if ($this->db->columnExist('settings', 'SYSTEM_USE_NOTIFICATION')) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN SYSTEM_USE_NOTIFICATION");
}
