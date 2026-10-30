<?php
/**
 *  6.2.0 update
 */

// Add 'Relaunched_from_task_id' column to tasks table
if (!$this->db->columnExist('tasks', 'Relaunched_from_task_id')) {
    try {
        $this->db->exec("ALTER TABLE tasks ADD COLUMN Relaunched_from_task_id INTEGER");
    } catch (Exception $e) {
        throw new Exception('could not add Relaunched_from_task_id column to tasks table');
    }
}

// Create index on 'Relaunched_from_task_id' column
try {
    $this->db->exec("CREATE INDEX IF NOT EXISTS tasks_relaunched_from_task_id ON tasks (Relaunched_from_task_id)");
} catch (Exception $e) {
    throw new Exception('could not create tasks_relaunched_from_task_id index: ' . $e->getMessage());
}
