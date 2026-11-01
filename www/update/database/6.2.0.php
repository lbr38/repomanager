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

/**
 *  Repositories built from a source repository (previously named 'mirror' repositories) are now named 'curated' repositories,
 *  'mirror' now designates upstream repositories copied as is
 */
try {
    $this->db->exec("UPDATE repos_snap SET Type = 'curated' WHERE Type = 'mirror'");

    // Tasks parameters (scheduled tasks and history), including sub-tasks of grouped tasks
    $this->db->exec("UPDATE tasks SET Raw_params = REPLACE(Raw_params, '\"repo-type\":\"mirror\"', '\"repo-type\":\"curated\"') WHERE Raw_params LIKE '%\"repo-type\":\"mirror\"%'");
} catch (Exception $e) {
    throw new Exception('could not rename mirror repositories to curated repositories: ' . $e->getMessage());
}
