<?php
/**
 *  3.7.17 database update
 */

/**
 *  Add 'PROXY' column in settings table if not exists
 */
if ($this->db->columnExist('settings', 'PROXY') === false) {
    /**
     *  Add new 'PROXY' column in settings
     */
    $this->db->exec("ALTER TABLE settings ADD PROXY VARCHAR(255)");
}
