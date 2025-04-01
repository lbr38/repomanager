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

/**
 *  Add 'DEB_ALLOW_EMPTY_REPO' column to settings table
 */
if (!$this->db->columnExist('settings', 'DEB_ALLOW_EMPTY_REPO')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN DEB_ALLOW_EMPTY_REPO VARCHAR(255) DEFAULT 'false'");
}

/**
 *  Add 'TASK_CLEAN_OLDER_THAN' column to settings table
 */
if (!$this->db->columnExist('settings', 'TASK_CLEAN_OLDER_THAN')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN TASK_CLEAN_OLDER_THAN INTEGER DEFAULT '730'");
}
