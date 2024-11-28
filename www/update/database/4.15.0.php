<?php
/**
 *  4.15.0 database update
 */

/**
 *  Add 'NOPROXY' column in settings table if not exists
 */
if ($this->db->columnExist('settings', 'NOPROXY') === false) {
    /**
     *  Add new 'NOPROXY' column in settings
     */
    $this->db->exec("ALTER TABLE settings ADD NOPROXY VARCHAR(255)");
}