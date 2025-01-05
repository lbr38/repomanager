<?php
/**
 *  4.15.0 update
 */

/**
 *  Drop 'MANAGE_PROFILES' columns from 'settings table
 */
if ($this->db->columnExist('settings', 'MANAGE_PROFILES') === true) {
    $this->db->exec("ALTER TABLE settings DROP COLUMN MANAGE_PROFILES");
}
