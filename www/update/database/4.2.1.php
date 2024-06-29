<?php
/**
 *  4.2.1 database update
 */

/**
 *  Rename 'PLANS_REMINDERS_ENABLED' column to 'SCHEDULED_TASKS_REMINDERS'
 */
if ($this->db->columnExist('settings', 'PLANS_REMINDERS_ENABLED') === true) {
    $this->db->exec("ALTER TABLE settings RENAME COLUMN PLANS_REMINDERS_ENABLED TO SCHEDULED_TASKS_REMINDERS");
    $this->db->exec('VACUUM');
}
