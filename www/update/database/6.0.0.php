<?php
/**
 *  6.0.0 update
 */

// Open hosts database
try {
    $hostsDb = new \Models\Connection('hosts');
} catch (Exception $e) {
    throw new Exception('could not open hosts database: ' . $e->getMessage());
}

// Drop some indexes if exists
try {
    $this->db->exec("DROP INDEX IF EXISTS repos_env_index");
    $this->db->exec("DROP INDEX IF EXISTS repos_env_id_snap_index");
} catch (Exception $e) {
    throw new Exception('could not delete old indexes from database: ' . $e->getMessage());
}

// Delete Pkg_translation column from repos_snap database
if ($this->db->columnExist('repos_snap', 'Pkg_translation')) {
    try {
        $this->db->exec("ALTER TABLE repos_snap DROP COLUMN Pkg_translation");
    } catch (Exception $e) {
        throw new Exception('could not delete Pkg_translation column from repos_snap table');
    }
}

// Add Description column to repos table
if (!$this->db->columnExist('repos', 'Description')) {
    try {
        $this->db->exec("ALTER TABLE repos ADD COLUMN Description VARCHAR(255)");
    } catch (Exception $e) {
        throw new Exception('could not add Description column to repos table');
    }
}

// Add Tags column to repos table
if (!$this->db->columnExist('repos', 'Tags')) {
    try {
        $this->db->exec("ALTER TABLE repos ADD COLUMN Tags VARCHAR(255)");
    } catch (Exception $e) {
        throw new Exception('could not add Tags column to repos table');
    }
}

// Delete Description column from repos_env table
if ($this->db->columnExist('repos_env', 'Description')) {
    try {
        $this->db->exec("ALTER TABLE repos_env DROP COLUMN Description");
    } catch (Exception $e) {
        throw new Exception('could not delete Description column from repos_env table');
    }
}

// Add 'Protected' column to env table
if (!$this->db->columnExist('env', 'Protected')) {
    try {
        $this->db->exec("ALTER TABLE env ADD COLUMN Protected CHAR(5) DEFAULT 'false'");
    } catch (Exception $e) {
        throw new Exception('could not add Protected column to env table');
    }
}

// Add 'Parent_task_id' column to tasks table
if (!$this->db->columnExist('tasks', 'Parent_task_id')) {
    try {
        $this->db->exec("ALTER TABLE tasks ADD COLUMN Parent_task_id INTEGER");
    } catch (Exception $e) {
        throw new Exception('could not add Parent_task_id column to tasks table');
    }
}

// Create index on 'Parent_task_id' column
try {
    $this->db->exec("CREATE INDEX IF NOT EXISTS tasks_parent_task_id ON tasks (Parent_task_id)");
} catch (Exception $e) {
    throw new Exception('could not create tasks_parent_task_id index: ' . $e->getMessage());
}

/**
 *  A scheduled task is now consumed by the execution it triggers and only groups the sub-tasks of
 *  that single execution. Scheduled tasks that are still waiting for their next occurrence would
 *  otherwise keep the sub-tasks of their previous executions attached, and summarize them all at
 *  once, so those sub-tasks are detached and kept in the tasks list as standalone tasks.
 */
try {
    $this->db->exec("UPDATE tasks SET Parent_task_id = NULL
    WHERE Parent_task_id IN (SELECT Id FROM tasks WHERE Status = 'scheduled' OR Status = 'disabled')");
} catch (Exception $e) {
    throw new Exception('could not detach sub-tasks from pending scheduled tasks: ' . $e->getMessage());
}

/**
 *  Scheduling an action on several repositories used to create one independent task per repository.
 *  Such an action now creates a single task holding the parameters of each of its sub-tasks, so the
 *  pending tasks that were created together are merged into one.
 *  Tasks sharing the same action, the same schedule and the same status always ran at the same
 *  moment, so merging them does not change when nor how they are executed.
 */
try {
    $taskGroups = [];

    $result = $this->db->query("SELECT Id, Status, Raw_params FROM tasks
    WHERE Type = 'scheduled' AND Parent_task_id IS NULL AND (Status = 'scheduled' OR Status = 'disabled')
    ORDER BY Id");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $params = json_decode($row['Raw_params'], true);

        // Ignore tasks that are already grouped, or that target a dynamic set of repositories
        if (empty($params['action']) or !empty($params['tasks']) or !empty($params['target'])) {
            continue;
        }

        $key = $row['Status'] . '-' . md5(json_encode([$params['action'], $params['schedule'] ?? []]));

        $taskGroups[$key]['status'] = $row['Status'];
        $taskGroups[$key]['ids'][] = (int) $row['Id'];
        $taskGroups[$key]['params'][] = $params;
    }

    foreach ($taskGroups as $taskGroup) {
        // A task that was alone keeps running on its own, there is nothing to group
        if (count($taskGroup['ids']) < 2) {
            continue;
        }

        $stmt = $this->db->prepare("INSERT INTO tasks (Type, Raw_params, Status) VALUES ('scheduled', :rawParams, :status)");
        $stmt->bindValue(':rawParams', json_encode([
            'action'   => $taskGroup['params'][0]['action'],
            'schedule' => $taskGroup['params'][0]['schedule'],
            'tasks'    => $taskGroup['params']
        ]));
        $stmt->bindValue(':status', $taskGroup['status']);
        $stmt->execute();

        $this->db->exec("DELETE FROM tasks WHERE Id IN (" . implode(',', $taskGroup['ids']) . ")");
    }
} catch (Exception $e) {
    throw new Exception('could not group existing scheduled tasks: ' . $e->getMessage());
}

// Add 'compliance_security_update' column to settings table
if (!$hostsDb->columnExist('settings', 'compliance_security_update')) {
    try {
        $hostsDb->exec("ALTER TABLE settings ADD COLUMN compliance_security_update INTEGER NOT NULL DEFAULT 1");
    } catch (Exception $e) {
        throw new Exception('could not add compliance_security_update column to settings table');
    }
}

// Add some columns to hosts database
try {
    $result = $hostsDb->query("SELECT Id FROM hosts");
    $hostIds = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hostIds[] = $row['Id'];
    }

    foreach ($hostIds as $hostId) {
        try {
            $hostDb = new \Models\Connection('host', $hostId);

            // Add 'Security' column to packages table
            if (!$hostDb->columnExist('packages', 'Security')) {
                $hostDb->exec("ALTER TABLE packages ADD COLUMN Security CHAR(5)");
            }

            // Add 'Security' column to packages_history table
            if (!$hostDb->columnExist('packages_history', 'Security')) {
                $hostDb->exec("ALTER TABLE packages_history ADD COLUMN Security CHAR(5)");
            }

            // Add 'Security' column to packages_available table
            if (!$hostDb->columnExist('packages_available', 'Security')) {
                $hostDb->exec("ALTER TABLE packages_available ADD COLUMN Security CHAR(5)");
            }
        } catch (Exception $e) {
            // If it fails for a specific host, just drop its database to reset it
            if (file_exists(HOSTS_DIR . '/' . $hostId . '/properties.db')) {
                if (!unlink(HOSTS_DIR . '/' . $hostId . '/properties.db')) {
                    throw new Exception('could not reset host #' . $hostId . ' database: ' . $e->getMessage());
                }
            }
        }
    }
} catch (Exception $e) {
    throw new Exception('could not update host databases: ' . $e->getMessage());
}
