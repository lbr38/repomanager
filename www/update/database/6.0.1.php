<?php
/**
 *  6.0.1 update
 */

try {
    if ($this->db->columnExist('tasks', 'Pid')) {
        $this->db->exec("ALTER TABLE tasks DROP COLUMN Pid");
    }

    if ($this->db->columnExist('tasks', 'Logfile')) {
        $this->db->exec("ALTER TABLE tasks DROP COLUMN Logfile");
    }
} catch (Exception $e) {
    throw new Exception('could not drop columns from tasks database: ' . $e->getMessage());
}
