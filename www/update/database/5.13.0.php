<?php
/**
 *  5.13.0 update
 */

// Open hosts database
try {
    $hostsDb = new \Models\Connection('hosts');
} catch (Exception $e) {
    throw new Exception('could not open hosts database: ' . $e->getMessage());
}

// Drop some indexes if exists
try {
    $hostsDb->exec("DROP INDEX IF EXISTS idx_hosts");
} catch (Exception $e) {
    throw new Exception('could not delete old indexes from hosts table: ' . $e->getMessage());
}

// Rename 'Linupdate_version' column to 'Agent_version' in 'hosts'
if ($hostsDb->columnExist('hosts', 'Linupdate_version')) {
    try {
        // First create the new column 'Agent_version'
        $hostsDb->exec("ALTER TABLE hosts ADD COLUMN Agent_version VARCHAR(255)");
        // Then copy the data from 'Linupdate_version' to 'Agent_version'
        $hostsDb->exec("UPDATE hosts SET Agent_version = Linupdate_version");
        // Finally, drop the old column 'Linupdate_version'
        $hostsDb->exec("ALTER TABLE hosts DROP COLUMN Linupdate_version");
    } catch (Exception $e) {
        throw new Exception('could not migrate Linupdate_version to Agent_version in hosts table: ' . $e->getMessage());
    }
}

// Create new indexes
try {
    $hostsDb->exec("CREATE INDEX IF NOT EXISTS idx_hosts ON hosts (Ip, Hostname, Os, Os_version, Os_family, Kernel, Arch, Type, Profile, Env, AuthId, Token, Online_status, Online_status_date, Online_status_time, Reboot_required, Agent_version)");
} catch (Exception $e) {
    throw new Exception('could not create new indexes for hosts table: ' . $e->getMessage());
}

try {
    // Add 'compliance_threshold_count' column if not exists
    if (!$hostsDb->columnExist('settings', 'compliance_threshold_count')) {
        $hostsDb->exec("ALTER TABLE settings ADD COLUMN compliance_threshold_count INTEGER NOT NULL DEFAULT 1");
    }

    // Add 'compliance_threshold_days' column if not exists
    if (!$hostsDb->columnExist('settings', 'compliance_threshold_days')) {
        $hostsDb->exec("ALTER TABLE settings ADD COLUMN compliance_threshold_days INTEGER NOT NULL DEFAULT 30");
    }

    // Add 'compliance_reboot_required' column if not exists
    if (!$hostsDb->columnExist('settings', 'compliance_reboot_required')) {
        $hostsDb->exec("ALTER TABLE settings ADD COLUMN compliance_reboot_required INTEGER NOT NULL DEFAULT 1"); /* 1 = true, 0 = false */
    }
} catch (Exception $e) {
    throw new Exception('could not create new columns in hosts settings table: ' . $e->getMessage());
}

try {
    // Overwrite settings with old values if they exist
    if ($hostsDb->columnExist('settings', 'pkgs_count_considered_outdated')) {
        $hostsDb->exec("UPDATE settings SET compliance_threshold_count = (SELECT pkgs_count_considered_outdated FROM settings)");
    }
} catch (Exception $e) {
    throw new Exception('could not migrate old settings to new compliance_threshold_count in hosts settings table: ' . $e->getMessage());
}

try {
    // Drop old 'pkgs_count_considered_outdated' column from settings table
    if ($hostsDb->columnExist('settings', 'pkgs_count_considered_outdated')) {
        $hostsDb->exec("ALTER TABLE settings DROP COLUMN pkgs_count_considered_outdated");
    }

    // Drop old 'pkgs_count_considered_critical' column from settings table
    if ($hostsDb->columnExist('settings', 'pkgs_count_considered_critical')) {
        $hostsDb->exec("ALTER TABLE settings DROP COLUMN pkgs_count_considered_critical");
    }
} catch (Exception $e) {
    throw new Exception('could not drop old settings columns in hosts settings table: ' . $e->getMessage());
}

// Drop old indexes on packages and packages_history tables in each host database
try {
    $result = $hostsDb->query("SELECT Id FROM hosts");
    $hostIds = [];

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $hostIds[] = $row['Id'];
    }

    foreach ($hostIds as $hostId) {
        try {
            $hostDb = new \Models\Connection('host', $hostId);
            $hostDb->exec("DROP INDEX IF EXISTS host_packages_state_date");
            $hostDb->exec("DROP INDEX IF EXISTS host_packages_history_state_date");
        } catch (Exception $e) {
            // Non-blocking: log and continue to next host
        }
    }
} catch (Exception $e) {
    // Non-blocking
}
