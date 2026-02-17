<?php
/**
 *  5.8.0 update
 */
$statsDb = new \Models\Connection('stats');
$hostsDb = new \Models\Connection('hosts');

// Add MIRRORING_PACKAGE_DOWNLOAD_RETRIES column to settings database
if (!$this->db->columnExist('settings', 'MIRRORING_PACKAGE_DOWNLOAD_RETRIES')) {
    try {
        $this->db->exec("ALTER TABLE settings ADD COLUMN MIRRORING_PACKAGE_DOWNLOAD_RETRIES INTEGER DEFAULT 3");
    } catch (Exception $e) {
        throw new Exception('could not add MIRRORING_PACKAGE_DOWNLOAD_RETRIES column to settings database: ' . $e->getMessage());
    }
}

// Add 'Network' column to hosts database
if (!$hostsDb->columnExist('hosts', 'Network')) {
    try {
        $hostsDb->exec("ALTER TABLE hosts ADD COLUMN Network VARCHAR(255)");
    } catch (Exception $e) {
        throw new Exception('could not add Network column to hosts database: ' . $e->getMessage());
    }
}

try {
    // Delete existing indexes
    $statsDb->exec("DROP INDEX IF EXISTS access_deb_index");
    $statsDb->exec("DROP INDEX IF EXISTS access_deb_name_env_index");
    $statsDb->exec("DROP INDEX IF EXISTS access_rpm_index");
    $statsDb->exec("DROP INDEX IF EXISTS access_rpm_name_env_index");
    $statsDb->exec("DROP INDEX IF EXISTS stats_index");
} catch (Exception $e) {
    throw new Exception('could not delete existing indexes from stats database: ' . $e->getMessage());
}

try {
    // Get current UTC offset in seconds for timezone correction
    $timezone = new DateTimeZone(date_default_timezone_get());
    $datetime = new DateTime('now', $timezone);
    $utcOffset = $timezone->getOffset($datetime);

    // Add 'Timestamp' column to access_deb table
    if (!$statsDb->columnExist('access_deb', 'Timestamp')) {
        $statsDb->exec("ALTER TABLE access_deb ADD COLUMN Timestamp INTEGER");

        // Update 'Timestamp' column for existing entries in access_deb table with UTC correction
        $statsDb->exec("UPDATE access_deb SET Timestamp = (strftime('%s', datetime(Date || ' ' || Time)) - " . $utcOffset . ") WHERE Timestamp IS NULL");
    }

    // Add 'Timestamp' column to access_rpm table
    if (!$statsDb->columnExist('access_rpm', 'Timestamp')) {
        $statsDb->exec("ALTER TABLE access_rpm ADD COLUMN Timestamp INTEGER");

        // Update 'Timestamp' column for existing entries in access_rpm table with UTC correction
        $statsDb->exec("UPDATE access_rpm SET Timestamp = (strftime('%s', datetime(Date || ' ' || Time)) - " . $utcOffset . ") WHERE Timestamp IS NULL");
    }

    // Delete Date and Time columns from access_deb table
    if ($statsDb->columnExist('access_deb', 'Date')) {
        $statsDb->exec("ALTER TABLE access_deb DROP COLUMN Date");
    }
    if ($statsDb->columnExist('access_deb', 'Time')) {
        $statsDb->exec("ALTER TABLE access_deb DROP COLUMN Time");
    }

    // Delete Date and Time columns from access_rpm table
    if ($statsDb->columnExist('access_rpm', 'Date')) {
        $statsDb->exec("ALTER TABLE access_rpm DROP COLUMN Date");
    }
    if ($statsDb->columnExist('access_rpm', 'Time')) {
        $statsDb->exec("ALTER TABLE access_rpm DROP COLUMN Time");
    }
} catch (Exception $e) {
    throw new Exception('could not update stats database structure: ' . $e->getMessage());
}

try {
    // Recreate indexes
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_deb ON access_deb (Name, Dist, Section, Env, Source, IP, Request, Request_result, Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_rpm ON access_rpm (Name, Releasever, Env, Source, IP, Request, Request_result, Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_repo_stats ON repo_stats (Snapshot_date, Snapshot_size, Snapshot_packages_count, Id_repo, Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_repo_stats_id_repo ON repo_stats (Id_repo)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_repo_stats_timestamp ON repo_stats (Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_rpm_timestamp ON access_rpm (Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_deb_timestamp ON access_deb (Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_rpm_name_releasever_timestamp ON access_rpm (Name, Releasever, Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_deb_name_dist_section_timestamp ON access_deb (Name, Dist, Section, Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_rpm_name_releasever_env_timestamp ON access_rpm (Name, Releasever, Env, Timestamp)");
    $statsDb->exec("CREATE INDEX IF NOT EXISTS idx_access_deb_name_dist_section_env_timestamp ON access_deb (Name, Dist, Section, Env, Timestamp)");
} catch (Exception $e) {
    throw new Exception('could not create indexes on stats database: ' . $e->getMessage());
}

// Drop old stats table
try {
    $statsDb->exec("DROP TABLE IF EXISTS stats");
} catch (Exception $e) {
    throw new Exception('could not create repo_stats table: ' . $e->getMessage());
}
