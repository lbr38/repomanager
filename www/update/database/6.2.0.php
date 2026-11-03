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
 *  Repositories built from a source repository (previously named 'mirror' repositories) are now named 'filtered' repositories,
 *  'mirror' now designates upstream repositories copied as is
 */
try {
    $this->db->exec("UPDATE repos_snap SET Type = 'filtered' WHERE Type = 'mirror'");

    // Tasks parameters (scheduled tasks and history), including sub-tasks of grouped tasks
    $this->db->exec("UPDATE tasks SET Raw_params = REPLACE(Raw_params, '\"repo-type\":\"mirror\"', '\"repo-type\":\"filtered\"') WHERE Raw_params LIKE '%\"repo-type\":\"mirror\"%'");
} catch (Exception $e) {
    throw new Exception('could not rename mirror repositories to filtered repositories: ' . $e->getMessage());
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

/**
 *  Repositories and mirrors snapshots are now deleted from the database instead of being marked as 'deleted', and
 *  repositories with no snapshot left are deleted too. Tasks now store the details of the repository or mirror they
 *  target (repo-info, also for create tasks instead of the repo-id field) so that they can still be described once it is deleted.
 *  These details are stored in the existing tasks, while the repositories and mirrors are still in the database.
 *  Snapshots already marked as 'deleted' are kept for now, they will be purged in a next release.
 */
try {
    $snaps = [];
    $repos = [];
    $mirrors = [];

    $result = $this->db->query("SELECT repos.Id AS repoId, repos.Name, repos.Releasever, repos.Dist, repos.Section, repos.Source, repos.Package_type, repos_snap.Id AS snapId, repos_snap.Arch, repos_snap.Date
    FROM repos
    LEFT JOIN repos_snap ON repos_snap.Id_repo = repos.Id");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $info = array_filter([
            'name'         => $row['Name'],
            'package-type' => $row['Package_type'],
            'releasever'   => $row['Releasever'],
            'dist'         => $row['Dist'],
            'section'      => $row['Section'],
            'source'       => $row['Source'],
            'arch'         => !empty($row['Arch']) ? explode(',', $row['Arch']) : [],
            'date'         => $row['Date']
        ]);

        if (!empty($row['snapId'])) {
            $snaps[$row['snapId']] = $info;
        }

        // A task targeting a repository by its Id has no snapshot details
        unset($info['arch'], $info['date']);
        $repos[$row['repoId']] = $info;
    }

    $result = $this->db->query("SELECT Id, Name, Releasever, Package_type, Source FROM mirrors");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $mirrors[$row['Id']] = array_filter([
            'name'         => $row['Name'],
            'package-type' => $row['Package_type'],
            'releasever'   => $row['Package_type'] == 'rpm' ? $row['Releasever'] : '',
            'source'       => $row['Source']
        ]);
    }

    // Add the details of the targeted repository or mirror to the parameters of a task, and of its sub-tasks
    $addInfo = function (array $params) use (&$addInfo, $snaps, $repos, $mirrors): array {
        if (!empty($params['tasks']) and is_array($params['tasks'])) {
            foreach ($params['tasks'] as $key => $subTaskParams) {
                $params['tasks'][$key] = $addInfo($subTaskParams);
            }

            return $params;
        }

        // Create tasks of repositories stored the name / dist / section in the repo-id field, replaced by repo-info
        if (($params['action'] ?? '') == 'create' and ($params['domain'] ?? '') != 'mirror' and isset($params['repo-id']) and !is_numeric($params['repo-id'])) {
            unset($params['repo-id']);
        }

        if (!empty($params['repo-info'])) {
            return $params;
        }

        // The repository or mirror to create is described by the task parameters
        if (($params['action'] ?? '') == 'create') {
            $packageType = $params['package-type'] ?? '';
            $isMirror = ($params['domain'] ?? '') == 'mirror';
            $name = $params['name'] ?? '';

            if (!$isMirror) {
                $name = !empty($params['alias']) ? $params['alias'] : ($params['source'] ?? '');
            }

            $info = array_filter([
                'name'         => $name,
                'package-type' => $packageType,
                'releasever'   => $packageType == 'rpm' ? ($params['releasever'] ?? '') : '',
                'dist'         => ($packageType == 'deb' && !$isMirror) ? ($params['dist'] ?? '') : '',
                'section'      => ($packageType == 'deb' && !$isMirror) ? ($params['section'] ?? '') : '',
                'source'       => $params['source'] ?? '',
                'arch'         => !$isMirror ? ($params['arch'] ?? []) : []
            ]);

            // Values are single strings once the task has been split (one task per release version / distribution / section)
            if (!empty($info['name']) and !is_array($info['releasever'] ?? '') and !is_array($info['dist'] ?? '') and !is_array($info['section'] ?? '')) {
                $params['repo-info'] = $info;
            }

            return $params;
        }

        if (($params['domain'] ?? '') == 'mirror') {
            $mirrorId = $params['mirror-id'] ?? '';

            if (is_numeric($mirrorId) and isset($mirrors[$mirrorId])) {
                $params['repo-info'] = $mirrors[$mirrorId];
            }

            return $params;
        }

        $snapId = $params['snap-id'] ?? '';
        $repoId = $params['repo-id'] ?? '';

        if (is_numeric($snapId) and isset($snaps[$snapId])) {
            $params['repo-info'] = $snaps[$snapId];
        } elseif (is_numeric($repoId) and isset($repos[$repoId])) {
            $params['repo-info'] = $repos[$repoId];
        }

        return $params;
    };

    $result = $this->db->query("SELECT Id, Raw_params FROM tasks");

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $params = json_decode($row['Raw_params'], true);

        if (!is_array($params)) {
            continue;
        }

        $newParams = $addInfo($params);

        if ($newParams == $params) {
            continue;
        }

        $stmt = $this->db->prepare("UPDATE tasks SET Raw_params = :params WHERE Id = :id");
        $stmt->bindValue(':params', json_encode($newParams, JSON_THROW_ON_ERROR));
        $stmt->bindValue(':id', $row['Id']);
        $stmt->execute();
    }
} catch (Exception $e) {
    throw new Exception('could not store repositories details in tasks parameters: ' . $e->getMessage());
}
