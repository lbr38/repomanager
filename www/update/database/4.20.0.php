<?php
/**
 *  4.20.0 update
 */

/**
 *  Add 'Service_reload' column to profile table
 */
if (!$this->db->columnExist('profile', 'Service_reload')) {
    $this->db->exec("ALTER TABLE profile ADD COLUMN Service_reload VARCHAR(255)");
}
