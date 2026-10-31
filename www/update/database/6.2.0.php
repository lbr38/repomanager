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

// Create exact mirrors tables
try {
    $this->db->exec("CREATE TABLE IF NOT EXISTS mirrors (
    Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    Name VARCHAR(255) NOT NULL,
    Releasever VARCHAR(255) NOT NULL DEFAULT '',
    Package_type VARCHAR(10) NOT NULL,
    Source VARCHAR(255) NOT NULL,
    Params TEXT,
    Description VARCHAR(255))");
    // A mirror is unique by package type, name and release version (rpm only)
    // Development versions of 6.2.0 had a unique name, update the table if needed
    if (!$this->db->columnExist('mirrors', 'Releasever')) {
        $this->db->exec("ALTER TABLE mirrors ADD COLUMN Releasever VARCHAR(255) NOT NULL DEFAULT ''");
    }
    // Development versions of 6.2.0 had no tags
    if (!$this->db->columnExist('mirrors', 'Tags')) {
        $this->db->exec("ALTER TABLE mirrors ADD COLUMN Tags VARCHAR(255)");
    }

    $this->db->exec("DROP INDEX IF EXISTS idx_mirrors_name");
    $this->db->exec("DROP INDEX IF EXISTS idx_mirrors_name_releasever");
    $this->db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_mirrors_type_name_releasever ON mirrors (Package_type, Name, Releasever)");

    $this->db->exec("CREATE TABLE IF NOT EXISTS mirrors_snap (
    Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    Date DATE NOT NULL,
    Time TIME NOT NULL,
    Size INTEGER,
    Status CHAR(8) NOT NULL,
    Id_mirror INTEGER NOT NULL)");
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_mirrors_snap_status_id_mirror ON mirrors_snap (Status, Id_mirror)");

    $this->db->exec("CREATE TABLE IF NOT EXISTS mirrors_env (
    Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    Env VARCHAR(255) NOT NULL,
    Id_snap INTEGER NOT NULL)");
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_mirrors_env_id_snap ON mirrors_env (Id_snap)");

    $this->db->exec("CREATE TABLE IF NOT EXISTS mirror_group_members (
    Id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    Id_mirror INTEGER NOT NULL,
    Id_group INTEGER NOT NULL)");
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_mirror_group_members_id_mirror ON mirror_group_members (Id_mirror)");
    $this->db->exec("CREATE INDEX IF NOT EXISTS idx_mirror_group_members_id_group ON mirror_group_members (Id_group)");
} catch (Exception $e) {
    throw new Exception('could not create mirrors tables: ' . $e->getMessage());
}

/**
 *  Repositories built from a source repository (previously named 'mirror' repositories) are now named 'curated' repositories,
 *  'mirror' now designates upstream repositories copied as is
 */
try {
    $this->db->exec("UPDATE repos_snap SET Type = 'curated' WHERE Type = 'mirror'");

    // Tasks parameters (scheduled tasks and history), including sub-tasks of grouped tasks
    $this->db->exec("UPDATE tasks SET Raw_params = REPLACE(Raw_params, '\"repo-type\":\"mirror\"', '\"repo-type\":\"curated\"') WHERE Raw_params LIKE '%\"repo-type\":\"mirror\"%'");

    // Development versions of 6.2.0 identified mirrors tasks with the 'exact' domain
    $this->db->exec("UPDATE tasks SET Raw_params = REPLACE(Raw_params, '\"domain\":\"exact\"', '\"domain\":\"mirror\"') WHERE Raw_params LIKE '%\"domain\":\"exact\"%'");
} catch (Exception $e) {
    throw new Exception('could not rename mirror repositories to curated repositories: ' . $e->getMessage());
}
