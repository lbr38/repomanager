<?php
/**
 *  4.3.0 database update
 */

/**
 *  Quit if 'TASK_EXECUTION_MEMORY_LIMIT' column already exists in the settings table
 */
if ($this->db->columnExist('settings', 'TASK_EXECUTION_MEMORY_LIMIT') === true) {
    return;
}

/**
 *  Add 'TASK_EXECUTION_MEMORY_LIMIT' column to the settings table
 */
$this->db->exec('ALTER TABLE settings ADD COLUMN TASK_EXECUTION_MEMORY_LIMIT INTEGER');
$this->db->exec('UPDATE settings SET TASK_EXECUTION_MEMORY_LIMIT = 512');
$this->db->exec('VACUUM');
