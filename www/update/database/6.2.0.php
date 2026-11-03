<?php
/**
 *  6.2.0 update
 */

use Models\Connection;
use Controllers\Utils\Validate;

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

// Add 'Retention' column to repos and mirrors tables (NULL: global setting, -1: keep all snapshots)
foreach (['repos', 'mirrors'] as $table) {
    if (!$this->db->tableExist($table) or $this->db->columnExist($table, 'Retention')) {
        continue;
    }

    try {
        $this->db->exec("ALTER TABLE " . $table . " ADD COLUMN Retention INTEGER");
    } catch (Exception $e) {
        throw new Exception('could not add Retention column to ' . $table . ' table: ' . $e->getMessage());
    }
}

// Open hosts database
try {
    $hostsDb = new Connection('hosts');
} catch (Exception $e) {
    throw new Exception('could not open hosts database: ' . $e->getMessage());
}

/**
 *  Escape hostnames and IP addresses that have been stored unescaped when registering hosts
 *  Hostnames and IP addresses are escaped when stored,
 *  values containing raw HTML special characters have never been escaped
 */
try {
    $result = $hostsDb->query("SELECT Id, Hostname, Ip FROM hosts");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hostname = $row['Hostname'] ?? '';
        $ip = $row['Ip'] ?? '';

        if (!preg_match('/[<>"\']/', $hostname . $ip)) {
            continue;
        }

        $stmt = $hostsDb->prepare("UPDATE hosts SET Hostname = :hostname, Ip = :ip WHERE Id = :id");
        if (preg_match('/[<>"\']/', $hostname)) {
            $hostname = Validate::string($hostname);
        }

        if (preg_match('/[<>"\']/', $ip)) {
            $ip = Validate::string($ip);
        }

        $stmt->bindValue(':hostname', $hostname);
        $stmt->bindValue(':ip', $ip);
        $stmt->bindValue(':id', $row['Id']);
        $stmt->execute();
    }
} catch (Exception $e) {
    throw new Exception('could not escape hosts hostnames and IP addresses: ' . $e->getMessage());
}

/**
 *  Hosts tokens are now stored hashed (SHA-256)
 */
try {
    $result = $hostsDb->query("SELECT Id, Token FROM hosts WHERE Token NOT LIKE 'sha256:%'");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        if (empty($row['Token'])) {
            continue;
        }

        $stmt = $hostsDb->prepare("UPDATE hosts SET Token = :token WHERE Id = :id");
        $stmt->bindValue(':token', 'sha256:' . hash('sha256', $row['Token']));
        $stmt->bindValue(':id', $row['Id']);
        $stmt->execute();
    }
} catch (Exception $e) {
    throw new Exception('could not hash hosts tokens: ' . $e->getMessage());
}

// Create index on history table, used to count recent failed authentications
try {
    $this->db->exec("CREATE INDEX IF NOT EXISTS history_date_time ON history (Date, Time)");
} catch (Exception $e) {
    throw new Exception('could not create history_date_time index: ' . $e->getMessage());
}
