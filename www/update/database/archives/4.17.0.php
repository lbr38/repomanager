<?php
/**
 *  4.17.0 update
 */

/**
 *  Add 'TASK_QUEUING' column to settings table
 */
if (!$this->db->columnExist('settings', 'TASK_QUEUING')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN TASK_QUEUING BOOLEAN DEFAULT 'false'");
}

/**
 *  Add 'TASK_QUEUING_MAX_SIMULTANEOUS' column to settings table
 */
if (!$this->db->columnExist('settings', 'TASK_QUEUING_MAX_SIMULTANEOUS')) {
    $this->db->exec("ALTER TABLE settings ADD COLUMN TASK_QUEUING_MAX_SIMULTANEOUS INTEGER DEFAULT '2'");
}
